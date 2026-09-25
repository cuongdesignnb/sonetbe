<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Services\SettingsService;
use App\Services\Video\BunnyHlsRelayService;
use App\Services\Video\PlaybackSessionService;
use GuzzleHttp\Exception\ConnectException;

class LessonController extends Controller
{
    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        
        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'order' => 'required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'embed_url' => 'nullable|string|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['course_id'] = $courseId;
        $data['is_preview'] = $request->boolean('is_preview', false);

        if (array_key_exists('embed_url', $data)) {
            $data['embed_url'] = trim((string) ($data['embed_url'] ?? ''));
            if ($data['embed_url'] === '') {
                $data['embed_url'] = null;
            } else {
                $data['video_bunny_id'] = null;
                $data['video_bunny_library_id'] = null;
                $data['video_local_path'] = null;
                $data['video_url'] = null;
            }
        }

        $lesson = Lesson::create($data);

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $course = $lesson->course;
        
        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'order' => 'sometimes|required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'embed_url' => 'nullable|string|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if (array_key_exists('embed_url', $data)) {
            $data['embed_url'] = trim((string) ($data['embed_url'] ?? ''));
            if ($data['embed_url'] === '') {
                $data['embed_url'] = null;
            } else {
                $data['video_bunny_id'] = null;
                $data['video_bunny_library_id'] = null;
                $data['video_local_path'] = null;
                $data['video_url'] = null;
            }
        }
        $lesson->update($data);

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson
        ]);
    }

    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $course = $lesson->course;
        
        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lesson->delete();

        return response()->json([
            'message' => 'Lesson deleted successfully'
        ]);
    }

    /**
     * Update lesson duration from frontend (when video metadata is detected)
     * Only updates if current duration is null/0.
     */
    public function updateDuration(Request $request, $lessonId)
    {
        $request->validate([
            'duration' => 'required|integer|min:1'
        ]);

        $lesson = Lesson::findOrFail($lessonId);

        // Only update if duration is not already set
        if (!$lesson->duration || $lesson->duration <= 0) {
            $lesson->update(['duration' => $request->duration]);
        }

        return response()->json([
            'message' => 'Duration updated',
            'duration' => $lesson->duration
        ]);
    }

    public function uploadVideo(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $course = $lesson->course;
        
        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'video' => 'required|file|max:1048576', // Max 1GB
            'storage_type' => 'nullable|in:local,bunny'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $video = $request->file('video');
        $storageType = $request->input('storage_type', 'bunny');

        if ($storageType === 'bunny') {
            $libraryId = SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
            $videoApiKey = SettingsService::get('bunnycdn.video_api_key', config('bunnycdn.video_api_key'));
            if (!$libraryId || !$videoApiKey) {
                return response()->json([
                    'message' => 'Bunny Stream is not configured',
                    'error' => 'Missing BUNNY_CDN_VIDEO_LIBRARY_ID / BUNNY_CDN_VIDEO_API_KEY'
                ], 422);
            }
        }

        try {
            if ($storageType === 'bunny') {
                $result = $this->uploadToBunnyStream($video, $lesson);
                $lesson->update([
                    'video_bunny_id' => $result['video_id'],
                    'video_bunny_library_id' => $libraryId,
                    'video_url' => $result['video_url'] ?? null,
                    'duration' => $result['duration'] ?? null,
                    'video_local_path' => null,
                    'embed_url' => null
                ]);
            } else {
                $path = $video->store('videos', 'public');
                $lesson->update([
                    'video_local_path' => $path,
                    'video_url' => asset('storage/' . $path),
                    'video_bunny_id' => null,
                    'embed_url' => null
                ]);
            }

            return response()->json([
                'message' => 'Video uploaded successfully',
                'lesson' => $lesson
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Video upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function uploadToBunnyStream($video, $lesson)
    {
        $client = new Client();
        $libraryId = SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
        $apiKey = SettingsService::get('bunnycdn.video_api_key', config('bunnycdn.video_api_key'));

        // Create video entry
        $create = $client->post(
            "https://video.bunnycdn.com/library/{$libraryId}/videos",
            [
                'headers' => [
                    'AccessKey' => $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'title' => $lesson->title ?: ('Lesson ' . $lesson->id)
                ]
            ]
        );

        $payload = json_decode((string) $create->getBody(), true);
        $videoId = $payload['guid'] ?? $payload['videoGuid'] ?? $payload['id'] ?? null;
        if (!$videoId) {
            throw new \Exception('Failed to create Bunny Stream video');
        }

        // Upload video file (stream to avoid high memory usage)
        $stream = fopen($video->getPathname(), 'r');
        if ($stream === false) {
            throw new \Exception('Failed to open video file for upload');
        }

        try {
            $upload = $client->put(
                "https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}",
                [
                    'headers' => [
                        'AccessKey' => $apiKey,
                        'Content-Type' => 'application/octet-stream'
                    ],
                    'body' => $stream
                ]
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (!in_array($upload->getStatusCode(), [200, 201, 204], true)) {
            throw new \Exception('Failed to upload to Bunny Stream');
        }

        return [
            'video_id' => $videoId,
            'video_url' => $this->buildBunnyStreamPlaylistUrl($videoId),
        ];
    }

    private function buildBunnyStreamPlaylistUrl(string $videoId): string
    {
        $host = SettingsService::get('bunnycdn.stream_hostname', config('bunnycdn.stream_hostname', ''));
        $host = is_string($host) ? trim($host) : '';

        return app(BunnyHlsRelayService::class)->playlistUrl($host, $videoId) ?? '';
    }

    public function streamVideo($id)
    {
        $lesson = Lesson::findOrFail($id);
        $user = Auth::user();
        
        Log::info("StreamVideo Request - Lesson: {$id}, User: " . ($user ? $user->id : 'Guest'));

        // Check if user has access to this lesson
        if (!$lesson->is_preview) {
            if (!$user) {
                Log::warning("StreamVideo: Unauthorized guest access attempt for Lesson {$id}");
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            if (!$user->hasAccessToLesson($lesson->id)) {
                Log::warning("StreamVideo: Access denied for User {$user->id} to Lesson {$id}");
                return response()->json(['message' => 'Access denied'], 403);
            }
        }

        // For explicit embed URL, return for iframe (supports VdoCipher otp if configured)
        if ($lesson->embed_url) {
            $embed = trim((string) $lesson->embed_url);
            if ($embed !== '') {
                if (preg_match('/^vdocipher:(.+)$/i', $embed, $match) || preg_match('#^vdocipher://(.+)$#i', $embed, $match)) {
                    $videoId = trim($match[1] ?? '');
                    if ($videoId !== '') {
                        $embedHtml = $this->buildVdoCipherEmbedHtml($videoId);
                        if ($embedHtml) {
                            return response()->json([
                                'embed_html' => $embedHtml,
                                'type' => 'vdocipher'
                            ]);
                        }
                    }
                }

                return response()->json([
                    'embed_url' => $embed,
                    'type' => 'custom_embed'
                ]);
            }
        }

        // For Bunny CDN - Check browser and return appropriate format
        if ($lesson->video_bunny_id) {
            // Use per-lesson library ID first, fallback to global setting
            $libraryId = $lesson->video_bunny_library_id
                ?: SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
            $libraryId = is_string($libraryId) ? trim($libraryId) : '';

            // 🛡️ SECURITY: Check for download browsers (Cốc Cốc, IDM, FDM, etc.)
            $userAgent = request()->header('User-Agent', '');
            
            // Advanced detection patterns
            $isBlockedBrowser = preg_match('/CocCoc|coc_coc_browser|coccoc|savior|FDM|Free Download|IDM|Internet Download|VideoCacheView|Video DownloadHelper|SaveFrom|Y2mate|Xunlei|Thunder/i', $userAgent);
            
            // Check Client Hints (Cốc Cốc often spoofs UA but forgets CH)
            $ch = request()->header('Sec-CH-UA', '') . ' ' . request()->header('Sec-CH-UA-Full-Version-List', '');
            if (!$isBlockedBrowser && stripos($ch, 'coccoc') !== false) {
                $isBlockedBrowser = true;
            }

            if ($isBlockedBrowser) {
                Log::warning("StreamVideo: Blocked browser detected for lesson {$id}", [
                    'user_agent' => $userAgent,
                    'ch' => $ch,
                    'ip' => request()->ip()
                ]);
                return response()->json([
                    'error' => true,
                    'error_code' => '6007',
                    'message' => 'Trình duyệt không được hỗ trợ. Vui lòng sử dụng Chrome, Firefox, Safari hoặc Edge.',
                    'type' => 'blocked'
                ], 403);
            }

            // Keep Bunny's player iframe as the normal primary route.
            if ($libraryId !== '') {
                return $this->bunnyPlaybackResponse($lesson, $libraryId);
            }
            return response()->json([
                'message' => 'Video playback configuration is unavailable.',
            ], 422);
        }

        // For local storage - direct URL (simple approach)
        if ($lesson->video_local_path) {
            return response()->json([
                'video_url' => asset('storage/' . $lesson->video_local_path),
                'type' => 'local'
            ]);
        }

        // Fallback: if a direct video URL exists, return as embed for iframe
        if ($lesson->video_url) {
            return response()->json([
                'embed_url' => $lesson->video_url,
                'type' => 'custom_embed'
            ]);
        }

        return response()->json(['message' => 'Video not available'], 404);
    }

    /**
     * Public endpoint for preview lesson videos (no auth required).
     * Only serves lessons marked as is_preview.
     */
    public function streamPreviewVideo($id)
    {
        $lesson = Lesson::findOrFail($id);

        if (!$lesson->is_preview) {
            return response()->json(['message' => 'This lesson is not available for preview.'], 403);
        }

        // Reuse the same video resolution logic as streamVideo
        // embed_url
        if ($lesson->embed_url) {
            $embed = trim((string) $lesson->embed_url);
            if ($embed !== '') {
                return response()->json([
                    'embed_url' => $embed,
                    'type' => 'custom_embed'
                ]);
            }
        }

        // Bunny CDN
        if ($lesson->video_bunny_id) {
            $libraryId = $lesson->video_bunny_library_id
                ?: SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
            $libraryId = is_string($libraryId) ? trim($libraryId) : '';
            if ($libraryId !== '') {
                return $this->bunnyPlaybackResponse($lesson, $libraryId);
            }
        }

        // Local storage
        if ($lesson->video_local_path) {
            return response()->json([
                'video_url' => asset('storage/' . $lesson->video_local_path),
                'type' => 'local'
            ]);
        }

        // Direct video URL
        if ($lesson->video_url) {
            return response()->json([
                'embed_url' => $lesson->video_url,
                'type' => 'custom_embed'
            ]);
        }

        return response()->json(['message' => 'Video not available'], 404);
    }

    private function bunnyPlaybackResponse(Lesson $lesson, string $libraryId)
    {
        $embedHostname = SettingsService::get('bunnycdn.embed_hostname', config('bunnycdn.embed_hostname', 'iframe.mediadelivery.net'));
        $embedHostname = is_string($embedHostname) ? strtolower(trim($embedHostname)) : '';
        if (!filter_var($embedHostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $embedHostname = 'iframe.mediadelivery.net';
        }

        $embedUrl = 'https://' . $embedHostname . '/embed/' . rawurlencode($libraryId) . '/' . rawurlencode((string) $lesson->video_bunny_id);
        $embedUrl .= '?autoplay=false&loop=false&muted=false&preload=true&responsive=true';
        $primary = [
            'type' => 'bunny_embed',
            'url' => $embedUrl,
            'origin' => 'https://' . $embedHostname,
        ];
        $fallback = null;
        $sessionId = null;
        $failoverConfigured = (bool) config('video.failover_enabled', false);
        $forceProxy = (bool) config('video.failover_force_proxy', false);
        $timeoutMs = (int) config('video.failover_timeout_ms', 10000);

        if ($failoverConfigured) {
            $streamHostname = $this->resolveBunnyStreamHostname($libraryId);
            $relay = app(BunnyHlsRelayService::class);
            $playlistUrl = $relay->playlistUrl($streamHostname, (string) $lesson->video_bunny_id);

            if ($playlistUrl !== null) {
                try {
                    $preview = (bool) $lesson->is_preview;
                    $session = app(PlaybackSessionService::class)->issue(
                        (int) $lesson->id,
                        $preview ? null : (int) Auth::id(),
                        $preview,
                        $streamHostname
                    );
                    $sessionId = $session['session_id'];
                    $fallback = [
                        'type' => 'hls_proxy',
                        'url' => '/api/backend/lessons/' . $lesson->id . '/hls?' . http_build_query([
                            'playback_token' => $session['token'],
                        ], '', '&', PHP_QUERY_RFC3986),
                    ];
                } catch (\Throwable) {
                    Log::warning('video_playback_session_issue_failed', [
                        'lesson_id' => (int) $lesson->id,
                        'error_category' => 'FALLBACK_TOKEN_ISSUE_ERROR',
                    ]);
                }
            }
        }

        $forceProxyActive = $failoverConfigured && $forceProxy && $fallback !== null;
        if ($forceProxyActive) {
            $primary = $fallback;
            $fallback = null;
        }

        return response()->json([
            // Keep these legacy fields during the frontend rollout.
            'embed_url' => $embedUrl,
            'type' => 'bunny_embed',
            'primary' => $primary,
            'fallback' => $fallback,
            'failover' => [
                'enabled' => $failoverConfigured && ($forceProxyActive || $fallback !== null),
                'timeout_ms' => max(1000, min(30000, $timeoutMs)),
                'force_proxy' => $forceProxyActive,
            ],
            'playback_session_id' => $sessionId,
        ]);
    }

    public function playbackEvent(Request $request, $id)
    {
        $lessonId = (int) $id;
        $event = (string) $request->input('event', '');
        $allowedEvents = [
            'video_primary_requested',
            'video_primary_ready',
            'video_primary_timeout',
            'video_primary_error',
            'video_fallback_requested',
            'video_fallback_ready',
            'video_fallback_error',
        ];
        $sessionId = (string) $request->input('playback_session_id', '');
        $playbackSessions = app(PlaybackSessionService::class);
        $session = $playbackSessions->resolveTelemetrySession($sessionId, $lessonId);
        if ($session === null) {
            return response()->noContent(403);
        }
        $lesson = Lesson::query()->select(['id', 'course_id'])->find($lessonId);
        if (!$lesson) {
            return response()->noContent(403);
        }

        if (!in_array($event, $allowedEvents, true)) {
            return response()->noContent(204);
        }

        $eventLimitKey = 'video-playback-telemetry:' . $sessionId;
        if (RateLimiter::tooManyAttempts($eventLimitKey, 120)) {
            return response()->noContent(204);
        }
        RateLimiter::hit($eventLimitKey, 60);

        $allowedCategories = [
            'PRIMARY_TIMEOUT',
            'PRIMARY_IFRAME_ERROR',
            'BUNNY_DNS_ERROR',
            'BUNNY_CONNECT_TIMEOUT',
            'BUNNY_HTTP_403',
            'BUNNY_HTTP_404',
            'BUNNY_HTTP_5XX',
            'HLS_MANIFEST_ERROR',
            'HLS_SEGMENT_ERROR',
            'FALLBACK_TOKEN_INVALID',
            'FALLBACK_TOKEN_EXPIRED',
            'FALLBACK_UPSTREAM_ERROR',
        ];
        $errorCategory = (string) $request->input('error_category', '');
        if (!in_array($errorCategory, $allowedCategories, true)) {
            $errorCategory = '';
        }
        $provider = (string) $request->input('provider', '');
        if (!in_array($provider, ['bunny_embed', 'hls_proxy', 'direct'], true)) {
            $provider = 'unknown';
        }

        $userAgent = (string) $request->userAgent();
        $browser = match (true) {
            preg_match('/Edg\//i', $userAgent) === 1 => 'Edge',
            preg_match('/Chrome\//i', $userAgent) === 1 => 'Chrome',
            preg_match('/Firefox\//i', $userAgent) === 1 => 'Firefox',
            preg_match('/Safari\//i', $userAgent) === 1 => 'Safari',
            default => 'Other',
        };
        $platform = match (true) {
            preg_match('/Android/i', $userAgent) === 1 => 'Android',
            preg_match('/iPhone|iPad|iPod/i', $userAgent) === 1 => 'iOS',
            preg_match('/Windows/i', $userAgent) === 1 => 'Windows',
            preg_match('/Macintosh|Mac OS/i', $userAgent) === 1 => 'macOS',
            preg_match('/Linux/i', $userAgent) === 1 => 'Linux',
            default => 'Other',
        };

        $requestId = (string) Str::uuid();
        Log::info('video_playback_event', [
            'request_id' => $requestId,
            'event' => $event,
            'lesson_id' => $lessonId,
            'course_id' => (int) $lesson->course_id,
            'playback_session_id' => $sessionId,
            'browser_family' => $browser,
            'platform' => $platform,
            'provider' => $provider,
            'elapsed_ms' => max(0, min(3600000, (int) $request->input('elapsed_ms', 0))),
            'error_category' => $errorCategory !== '' ? $errorCategory : null,
        ]);

        return response()->noContent();
    }

    private function buildVdoCipherEmbedHtml(string $videoId): ?string
    {
        $apiSecret = env('VDOCIPHER_API_SECRET');
        if (!is_string($apiSecret) || trim($apiSecret) === '') {
            return null;
        }

        $apiSecret = trim($apiSecret);
        $apiKey = env('VDOCIPHER_API_KEY');
        $apiKey = is_string($apiKey) ? trim($apiKey) : '';
        $playerId = env('VDOCIPHER_PLAYER_ID');
        $playerId = is_string($playerId) ? trim($playerId) : '';

        $client = new Client();
        $headers = [
            'Authorization' => 'Apisecret ' . $apiSecret,
            'Content-Type' => 'application/json',
        ];
        if ($apiKey !== '') {
            $headers['VdoCipher-API-Key'] = $apiKey;
        }

        $response = $client->post("https://dev.vdocipher.com/api/videos/{$videoId}/otp", [
            'headers' => $headers,
            'json' => [
                'ttl' => 300,
            ],
            'http_errors' => false,
            'timeout' => 10,
        ]);

        if ($response->getStatusCode() >= 400) {
            return null;
        }

        $payload = json_decode((string) $response->getBody(), true);
        $otp = $payload['otp'] ?? null;
        $playbackInfo = $payload['playbackInfo'] ?? null;
        if (!is_string($otp) || !is_string($playbackInfo)) {
            return null;
        }

        $params = [
            'otp' => $otp,
            'playbackInfo' => $playbackInfo,
        ];
        if ($playerId !== '') {
            $params['player'] = $playerId;
        }

        $src = 'https://player.vdocipher.com/v2/?' . http_build_query($params);

        return '<iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" style="border:0; width:100%; height:100%;" allow="encrypted-media; autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
    }

    public function proxyHls(Request $request, $id)
    {
        $requestId = (string) Str::uuid();
        $lessonId = (int) $id;
        $playbackToken = (string) $request->query('playback_token', '');
        $playbackSessions = app(PlaybackSessionService::class);

        if (!(bool) config('video.failover_enabled', false)) {
            return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 404);
        }

        try {
            $lesson = Lesson::find($lessonId);
            if (!$lesson || !$lesson->video_bunny_id) {
                return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 404);
            }

            $session = $playbackSessions->resolve($playbackToken, $lessonId);
            if ($session === null) {
                Log::warning('video_hls_relay_denied', [
                    'request_id' => $requestId,
                    'lesson_id' => $lessonId,
                    'error_category' => 'FALLBACK_TOKEN_INVALID',
                ]);
                return response()->json(['message' => 'Playback session invalid or expired', 'request_id' => $requestId], 403);
            }

            $isPreviewSession = (bool) ($session['preview'] ?? false);
            if ($isPreviewSession) {
                if (!$lesson->is_preview || ($session['user_id'] ?? null) !== null) {
                    return response()->json(['message' => 'Access denied', 'request_id' => $requestId], 403);
                }
            } else {
                $userId = (int) ($session['user_id'] ?? 0);
                $user = $userId > 0 ? User::query()->find($userId) : null;
                if (!$user || ($lesson->is_preview === false && !$user->hasAccessToLesson($lesson->id))) {
                    Log::warning('video_hls_relay_denied', [
                        'request_id' => $requestId,
                        'lesson_id' => $lessonId,
                        'playback_session_id' => (string) ($session['session_id'] ?? ''),
                        'error_category' => 'LESSON_AUTHORIZATION_DENIED',
                    ]);
                    return response()->json(['message' => 'Access denied', 'request_id' => $requestId], 403);
                }
            }

            // Entitlement is rechecked before extending the session's sliding TTL.
            $playbackSessions->touch($playbackToken, $session);

            $sessionId = (string) ($session['session_id'] ?? '');
            $sessionLimitKey = 'video-relay:session:' . $sessionId;
            $ipHash = hash('sha256', (string) ($request->ip() ?: 'unknown'));
            $ipLimitKey = 'video-relay:ip:' . $ipHash;
            if (RateLimiter::tooManyAttempts($sessionLimitKey, 900)
                || RateLimiter::tooManyAttempts($ipLimitKey, 3000)) {
                $limitedKey = RateLimiter::tooManyAttempts($sessionLimitKey, 900) ? $sessionLimitKey : $ipLimitKey;
                $retryAfter = max(1, RateLimiter::availableIn($limitedKey));
                return response()->json([
                    'message' => 'Video relay temporarily unavailable',
                    'request_id' => $requestId,
                ], 429)->header('Retry-After', (string) $retryAfter);
            }
            RateLimiter::hit($sessionLimitKey, 60);
            RateLimiter::hit($ipLimitKey, 60);

            $libraryId = $lesson->video_bunny_library_id
                ?: SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
            $libraryId = is_string($libraryId) || is_numeric($libraryId) ? trim((string) $libraryId) : '';

            // The playback session is bound to the exact Bunny Pull Zone resolved for
            // this lesson's library. Do not use one global Stream hostname: Sonet has
            // multiple Bunny libraries, each backed by a different Pull Zone.
            $streamHostname = trim((string) ($session['stream_hostname'] ?? ''));
            if ($streamHostname === '' && $libraryId !== '') {
                // Compatibility path for sessions issued before per-library binding.
                $streamHostname = $this->resolveBunnyStreamHostname($libraryId);
            }

            $relay = app(BunnyHlsRelayService::class);
            $baseUrl = $relay->playlistUrl($streamHostname, (string) $lesson->video_bunny_id);
            $path = $request->query('path', '');
            if (!is_string($path) || strlen($path) > 4096) {
                $path = '';
            }
            $targetUrl = $path === ''
                ? $baseUrl
                : ($baseUrl !== null ? $relay->resolveResource($baseUrl, $path, $streamHostname) : null);

            if ($targetUrl !== null
                && !$relay->resourceBelongsToVideo($targetUrl, (string) $lesson->video_bunny_id)) {
                Log::warning('video_hls_relay_denied', [
                    'request_id' => $requestId,
                    'lesson_id' => $lessonId,
                    'playback_session_id' => (string) ($session['session_id'] ?? ''),
                    'error_category' => 'TOKEN_VIDEO_RESOURCE_MISMATCH',
                ]);
                return response()->json(['message' => 'Access denied', 'request_id' => $requestId], 403);
            }

            if ($baseUrl === null || $targetUrl === null) {
                Log::warning('video_hls_relay_failed', [
                    'request_id' => $requestId,
                    'lesson_id' => $lessonId,
                    'playback_session_id' => $sessionId,
                    'error_category' => 'HLS_RESOURCE_REJECTED',
                ]);
                return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
            }

            $isPlaylist = str_ends_with(strtolower((string) parse_url($targetUrl, PHP_URL_PATH)), '.m3u8');
            $signedTargetUrl = $this->applyBunnyStreamSignedUrl($targetUrl, (string) $lesson->video_bunny_id);
            $siteUrl = rtrim((string) SettingsService::get('site.url', config('app.url')), '/');
            $headers = [
                'Accept' => '*/*',
                'Accept-Encoding' => 'identity',
                'User-Agent' => 'Sonet-Video-Relay/1.0',
            ];
            if ($siteUrl !== '') {
                $headers['Referer'] = $siteUrl . '/';
            }
            $range = $request->header('Range');
            if (is_string($range) && preg_match('/^bytes=\d*-\d*$/', $range)) {
                $headers['Range'] = $range;
            }

            $client = $relay->upstreamClient();
            $upstream = null;
            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    $upstream = $client->request('GET', $signedTargetUrl, [
                        'http_errors' => false,
                        'allow_redirects' => false,
                        'stream' => true,
                        'timeout' => 30,
                        'read_timeout' => 15,
                        'connect_timeout' => 5,
                        'headers' => $headers,
                    ]);
                    break;
                } catch (ConnectException $exception) {
                    if ($attempt === 0) {
                        usleep(350000);
                        continue;
                    }

                    Log::warning('video_hls_relay_failed', [
                        'request_id' => $requestId,
                        'lesson_id' => $lessonId,
                        'playback_session_id' => $sessionId,
                        'error_category' => 'BUNNY_CONNECT_TIMEOUT',
                    ]);
                    return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
                }
            }

            if ($upstream === null) {
                return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
            }

            $status = $upstream->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $errorCategory = match ($status) {
                    403 => 'BUNNY_HTTP_403',
                    404 => 'BUNNY_HTTP_404',
                    default => ($status >= 500 ? 'BUNNY_HTTP_5XX' : ($isPlaylist ? 'HLS_MANIFEST_ERROR' : 'HLS_SEGMENT_ERROR')),
                };
                Log::warning('video_hls_relay_failed', [
                    'request_id' => $requestId,
                    'lesson_id' => $lessonId,
                    'playback_session_id' => $sessionId,
                    'upstream_status' => $status,
                    'error_category' => $errorCategory,
                ]);
                return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
            }

            $contentType = $upstream->getHeaderLine('Content-Type');
            $headersOut = [
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ];

            if ($isPlaylist || str_contains(strtolower($contentType), 'mpegurl')) {
                $manifest = $upstream->getBody()->getContents();
                if (strlen($manifest) > 4 * 1024 * 1024) {
                    Log::warning('video_hls_relay_failed', [
                        'request_id' => $requestId,
                        'lesson_id' => $lessonId,
                        'playback_session_id' => $sessionId,
                        'error_category' => 'HLS_MANIFEST_ERROR',
                    ]);
                    return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
                }

                $rewritten = $relay->rewritePlaylist(
                    $manifest,
                    $targetUrl,
                    $streamHostname,
                    '/api/backend/lessons/' . $lessonId . '/hls',
                    $playbackToken
                );
                if ($rewritten === null) {
                    Log::warning('video_hls_relay_failed', [
                        'request_id' => $requestId,
                        'lesson_id' => $lessonId,
                        'playback_session_id' => $sessionId,
                        'error_category' => 'HLS_MANIFEST_ERROR',
                    ]);
                    return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
                }

                $headersOut['Content-Type'] = 'application/vnd.apple.mpegurl';
                return response($rewritten, 200, $headersOut);
            }

            $headersOut['Content-Type'] = $contentType ?: 'application/octet-stream';
            foreach (['Content-Range', 'Content-Length', 'Accept-Ranges', 'ETag', 'Last-Modified'] as $headerName) {
                $value = $upstream->getHeaderLine($headerName);
                if ($value !== '') {
                    $headersOut[$headerName] = $value;
                }
            }
            if (!isset($headersOut['Accept-Ranges'])) {
                $headersOut['Accept-Ranges'] = 'bytes';
            }

            $body = $upstream->getBody();
            return response()->stream(function () use ($body, $requestId, $lessonId, $sessionId) {
                try {
                    while (!$body->eof()) {
                        if (connection_aborted()) {
                            break;
                        }
                        $chunk = $body->read(65536);
                        if ($chunk === '') {
                            break;
                        }
                        echo $chunk;
                        flush();
                    }
                } catch (\Throwable) {
                    Log::warning('video_hls_relay_stream_interrupted', [
                        'request_id' => $requestId,
                        'lesson_id' => $lessonId,
                        'playback_session_id' => $sessionId,
                        'error_category' => 'HLS_SEGMENT_ERROR',
                    ]);
                } finally {
                    $body->close();
                }
            }, $status, $headersOut);
        } catch (\Throwable) {
            Log::warning('video_hls_relay_failed', [
                'request_id' => $requestId,
                'lesson_id' => $lessonId,
                'error_category' => 'FALLBACK_UPSTREAM_ERROR',
            ]);
            return response()->json(['message' => 'Video relay temporarily unavailable', 'request_id' => $requestId], 502);
        }
    }

    /**
     * Apply Bunny Stream signed URL authentication
     * Bunny Stream uses a different token format than CDN Pull Zones
     * Format: SHA256(token_key + video_id + expiration_time) as hex
     */
    /**
     * Resolve the system Pull Zone hostname for a Bunny Stream library.
     *
     * Sonet uses multiple Bunny libraries and each library has its own Pull Zone,
     * so a single global stream hostname cannot safely be used for HLS relay.
     * The account API is queried only on a cache miss and the resolved hostname is
     * cached for 24 hours. No credential or signed URL is logged.
     */
    private function resolveBunnyStreamHostname(string $libraryId): string
    {
        $libraryId = trim($libraryId);
        if ($libraryId === '' || !preg_match('/^\d+$/', $libraryId)) {
            return '';
        }

        $globalLibraryId = trim((string) SettingsService::get(
            'bunnycdn.video_library_id',
            config('bunnycdn.video_library_id', '')
        ));
        $globalHostname = trim((string) SettingsService::get(
            'bunnycdn.stream_hostname',
            config('bunnycdn.stream_hostname', '')
        ));

        // Preserve the validated configured hostname for the configured default
        // library and avoid an unnecessary Bunny API request.
        if ($libraryId === $globalLibraryId && $this->isSafeBunnyStreamHostname($globalHostname)) {
            return strtolower($globalHostname);
        }

        $cacheKey = 'bunny:stream-hostname:library:' . $libraryId;

        return (string) Cache::remember($cacheKey, now()->addHours(24), function () use ($libraryId): string {
            $accountApiKey = trim((string) SettingsService::get(
                'bunnycdn.api_key',
                config('bunnycdn.api_key', '')
            ));
            if ($accountApiKey === '') {
                return '';
            }

            try {
                $client = new Client([
                    'timeout' => 15,
                    'connect_timeout' => 5,
                ]);

                $libraryResponse = $client->get(
                    'https://api.bunny.net/videolibrary/' . rawurlencode($libraryId),
                    [
                        'headers' => [
                            'AccessKey' => $accountApiKey,
                            'Accept' => 'application/json',
                        ],
                    ]
                );

                $library = json_decode((string) $libraryResponse->getBody(), true);
                $pullZoneId = is_array($library)
                    ? ($library['PullZoneId'] ?? $library['pullZoneId'] ?? null)
                    : null;

                if (!is_numeric($pullZoneId)) {
                    return '';
                }

                $pullZoneResponse = $client->get(
                    'https://api.bunny.net/pullzone/' . rawurlencode((string) $pullZoneId),
                    [
                        'headers' => [
                            'AccessKey' => $accountApiKey,
                            'Accept' => 'application/json',
                        ],
                    ]
                );

                $pullZone = json_decode((string) $pullZoneResponse->getBody(), true);
                $hostnames = is_array($pullZone)
                    ? ($pullZone['Hostnames'] ?? $pullZone['hostnames'] ?? [])
                    : [];

                foreach (is_array($hostnames) ? $hostnames : [] as $hostname) {
                    if (!is_array($hostname)) {
                        continue;
                    }

                    $isSystem = (bool) ($hostname['IsSystemHostname'] ?? $hostname['isSystemHostname'] ?? false);
                    $value = trim((string) ($hostname['Value'] ?? $hostname['value'] ?? ''));

                    if ($isSystem && $this->isSafeBunnyStreamHostname($value)) {
                        return strtolower($value);
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('bunny_stream_hostname_resolve_failed', [
                    'library_id' => $libraryId,
                    'error_category' => 'BUNNY_PULL_ZONE_RESOLVE_ERROR',
                ]);
            }

            return '';
        });
    }

    private function isSafeBunnyStreamHostname(string $hostname): bool
    {
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        return $hostname !== ''
            && $hostname !== 'iframe.mediadelivery.net'
            && !filter_var($hostname, FILTER_VALIDATE_IP)
            && filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && str_ends_with($hostname, '.b-cdn.net');
    }

    private function applyBunnyStreamSignedUrl(string $url, string $videoId): string
    {
        $enabled = (bool) SettingsService::get('bunnycdn.enable_token_auth', config('bunnycdn.enable_token_auth', false));
        $key = (string) SettingsService::get('bunnycdn.token_auth_key', config('bunnycdn.token_auth_key', ''));

        // Log::info("Applying Bunny Stream Signed URL. Enabled: " . ($enabled ? 1 : 0) . ", VideoID: $videoId");
        
        // If token auth is not enabled or no key, return URL as-is
        if (!$enabled || $key === '') {
            return $url;
        }

        $ttl = (int) SettingsService::get('bunnycdn.token_ttl', config('bunnycdn.token_ttl', 3600));
        $ttl = max(60, $ttl);
        $expires = time() + $ttl;

        // Bunny Stream signed URL format:
        // token = SHA256(security_key + video_id + expiration_timestamp)
        // URL params: ?token=xxx&expires=timestamp
        // IMPORTANT: Ensure key is correct and matches the one in Bunny Stream > Security > API Key (or Token Authentication Key)
        $hashString = $key . $videoId . $expires;
        $token = hash('sha256', $hashString);

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $params = [];
        if ($query !== '') {
            parse_str($query, $params);
        }
        $params['token'] = $token;
        $params['expires'] = $expires;

        $newQuery = http_build_query($params);

        return $scheme . '://' . $host . $port . $path . ($newQuery !== '' ? '?' . $newQuery : '');
    }

    private function getPublicBaseUrl(Request $request): string
    {
        $forwardedProto = (string) $request->header('x-forwarded-proto', '');
        $forwardedHost = (string) $request->header('x-forwarded-host', '');

        $scheme = $forwardedProto !== '' ? $forwardedProto : $request->getScheme();
        $host = $forwardedHost !== '' ? $forwardedHost : $request->getHost();

        return rtrim($scheme . '://' . $host, '/');
    }

    private function streamLocalVideo(string $path)
    {
        $fileSize = filesize($path);
        $range = request()->header('range');

        $start = 0;
        $end = $fileSize - 1;

        if ($range) {
            $parts = explode('-', str_replace('bytes=', '', $range));
            $start = max(0, (int) $parts[0]);
            $end = isset($parts[1]) && $parts[1] !== '' ? min((int) $parts[1], $end) : $end;
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $length,
            'Cache-Control' => 'private, max-age=3600',
            // CORS headers for video streaming
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Accept-Encoding',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        ];

        $statusCode = $range ? 206 : 200;

        return response()->stream(function () use ($path, $start, $length) {
            $fp = fopen($path, 'rb');
            if ($fp === false) {
                return;
            }
            fseek($fp, $start);
            $remaining = $length;

            while ($remaining > 0 && !feof($fp)) {
                $read = min(65536, $remaining); // 64KB chunks
                $buffer = fread($fp, $read);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                flush();
                $remaining -= strlen($buffer);
                if (connection_aborted()) {
                    break;
                }
            }

            fclose($fp);
        }, $statusCode, $headers);
    }

    /**
     * Generate chunk token for next chunk request
     * This ensures chunks must be requested in sequence from same client
     */
    private function generateChunkToken(string $clientIp, string $userAgent, int $chunkIndex): string
    {
        $secret = config('video.video_secret') ?: config('app.key');
        if (is_string($secret) && str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        
        $timestamp = time();
        $data = $clientIp . '|' . substr($userAgent, 0, 50) . '|' . $chunkIndex . '|' . $timestamp;
        $token = hash_hmac('sha256', $data, $secret ?: '');
        
        // Store token in cache for verification (valid for 60 seconds)
        Cache::put('chunk_token:' . $token, [
            'ip' => $clientIp,
            'ua' => substr($userAgent, 0, 50),
            'chunk' => $chunkIndex,
            'time' => $timestamp,
        ], 60);
        
        return $token;
    }

    /**
     * Verify chunk token from previous response
     */
    private function verifyChunkToken(string $token, string $clientIp, string $userAgent): bool
    {
        $data = Cache::get('chunk_token:' . $token);
        
        if (!$data || !is_array($data)) {
            return false;
        }
        
        // Verify IP matches
        if ($data['ip'] !== $clientIp) {
            return false;
        }
        
        // Verify user agent matches (first 50 chars)
        if ($data['ua'] !== substr($userAgent, 0, 50)) {
            return false;
        }
        
        // Token is valid, remove it (one-time use)
        Cache::forget('chunk_token:' . $token);
        
        return true;
    }

    /**
     * Clean up expired cache entries periodically
     */
    private function cleanupExpiredEntries(): void
    {
        // This is handled automatically by Laravel's cache expiration
        // No manual cleanup needed
    }
}
