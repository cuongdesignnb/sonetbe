<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response; // Keep existing
use Illuminate\Support\Facades\Log; // Add Log
use GuzzleHttp\Client;
use App\Services\SettingsService;

class LessonController extends Controller
{
    // Constants for security
    private const RATE_LIMIT_WINDOW_MS = 60000; // 1 minute
    private const RATE_LIMIT_MAX_REQUESTS = 500; // HLS makes many requests
    private const BLACKLIST_DURATION_SECONDS = 300; // 5 minutes
    private const SIGNATURE_TTL_MS = 600000; // 10 minutes (video playback needs longer)
    private const MAX_CHUNK_SIZE = 2 * 1024 * 1024; // 2MB - balance between protection and performance
    private const SESSION_CONCURRENT_LIMIT = 50; // Max concurrent streams per user (HLS needs many)
    private const SESSION_TTL_SECONDS = 10; // Session check window (shorter for HLS)

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
        $libraryId = SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
        $host = SettingsService::get('bunnycdn.stream_hostname', config('bunnycdn.stream_hostname', 'iframe.mediadelivery.net'));
        $host = is_string($host) ? trim($host) : '';
        if ($host === '') {
            $host = 'iframe.mediadelivery.net';
        }

        if ($libraryId) {
            return "https://{$host}/{$videoId}/playlist.m3u8";
        }

        return "https://{$host}/{$videoId}/playlist.m3u8";
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

            // PRIMARY: Use Bunny embed URL when library ID is available (most reliable)
            if ($libraryId !== '') {
                $embedUrl = "https://iframe.mediadelivery.net/embed/{$libraryId}/{$lesson->video_bunny_id}";
                $embedUrl .= "?autoplay=false&loop=false&muted=false&preload=true&responsive=true";

                Log::info("StreamVideo: Returning Bunny embed URL for lesson {$id}", [
                    'embed_url' => $embedUrl,
                    'video_bunny_id' => $lesson->video_bunny_id,
                    'library_id' => $libraryId
                ]);

                return response()->json([
                    'embed_url' => $embedUrl,
                    'type' => 'bunny_embed'
                ]);
            }
            
            // FALLBACK: Direct HLS when no library ID is available (needs stream hostname + token auth)
            $streamHostname = SettingsService::get('bunnycdn.stream_hostname', config('bunnycdn.stream_hostname', ''));
            $streamHostname = is_string($streamHostname) ? trim($streamHostname) : '';
            $tokenAuthEnabled = (bool) SettingsService::get('bunnycdn.enable_token_auth', config('bunnycdn.enable_token_auth', false));
            $tokenAuthKey = (string) SettingsService::get('bunnycdn.token_auth_key', config('bunnycdn.token_auth_key', ''));
            $hasTokenAuth = $tokenAuthEnabled && $tokenAuthKey !== '';
            
            if ($streamHostname !== '' && $streamHostname !== 'iframe.mediadelivery.net' && $hasTokenAuth) {
                $hlsUrl = "https://{$streamHostname}/{$lesson->video_bunny_id}/playlist.m3u8";
                $hlsUrl = $this->applyBunnyStreamSignedUrl($hlsUrl, $lesson->video_bunny_id);

                Log::info("StreamVideo: Returning Direct HLS URL for lesson {$id} (no library ID, using stream hostname)", [
                    'hls_url' => $hlsUrl,
                    'video_bunny_id' => $lesson->video_bunny_id
                ]);

                return response()->json([
                    'video_url' => $hlsUrl,
                    'type' => 'hls'
                ]);
            }

            Log::error("StreamVideo: Bunny configuration incomplete for lesson {$id}. video_library_id='{$libraryId}', stream_hostname='{$streamHostname}', token_auth=" . ($hasTokenAuth ? 'yes' : 'no'));
            return response()->json([
                'message' => 'Cấu hình Bunny Stream chưa đầy đủ. Cần điền Video Library ID trong Settings, hoặc bật Token Auth nếu dùng Direct HLS.',
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
                $embedHostname = SettingsService::get('bunnycdn.embed_hostname', config('bunnycdn.embed_hostname', ''));
                $embedHostname = is_string($embedHostname) ? trim($embedHostname) : '';
                if ($embedHostname === '') $embedHostname = 'iframe.mediadelivery.net';
                $embedUrl = "https://{$embedHostname}/embed/{$libraryId}/{$lesson->video_bunny_id}";
                return response()->json([
                    'embed_url' => $embedUrl,
                    'type' => 'bunny_embed'
                ]);
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
        try {
            Log::info("ProxyHLS Request - Lesson: $id, IP: " . $request->ip());
            
            $lesson = Lesson::findOrFail($id);
            $user = Auth::user();
            if ($user && !$lesson->is_preview && !$user->hasAccessToLesson($lesson->id)) {
                Log::warning("ProxyHLS: Access denied (Not enrolled) for User {$user->id}");
                return response()->json(['message' => 'Access denied'], 403);
            }

            $clientIp = $this->getClientIp($request);
            $userAgent = (string) $request->header('User-Agent', '');

            // Security checks - protect against bulk downloads and simple tools
            if ($this->isDownloadManager($userAgent)) {
                Log::warning("ProxyHLS: Blocked Download Manager UA: $userAgent");
                return response()->json(['message' => 'Download managers are not allowed'], 403);
            }

            if ($this->isBlacklisted($clientIp)) {
                Log::warning("ProxyHLS: Blocked Blacklisted IP: $clientIp");
                return response()->json(['message' => 'IP address is blacklisted'], 403);
            }

            if (!$this->applyRateLimit($clientIp)) {
                $this->addToBlacklist($clientIp);
                Log::warning("ProxyHLS: Rate Limit Exceeded for IP: $clientIp");
                return response()->json(['message' => 'Too many requests'], 429);
            }

            // Note: Browser extensions like Cốc Cốc Savior can still intercept requests
            // Only DRM (Widevine/FairPlay) can fully protect against this

            $timestamp = (string) $request->query('timestamp', '');
            $signature = (string) $request->query('signature', '');
            $path = (string) $request->query('path', '');

            if ($timestamp === '' || $signature === '') {
                Log::warning("ProxyHLS: Missing signature/timestamp params");
                return response()->json(['message' => 'Missing signature'], 403);
            }

            if (!$this->verifySignature((string) $id, $timestamp, $signature, $path)) {
                Log::warning("ProxyHLS: Invalid local signature.");
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            if (!$lesson->video_bunny_id) {
                return response()->json(['message' => 'Video not available'], 404);
            }

            $baseUrl = $this->buildBunnyStreamPlaylistUrl($lesson->video_bunny_id);
            $targetUrl = $this->resolveBunnyUrl($baseUrl, $path);

            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);
            if (!$targetHost || ($baseHost && $targetHost !== $baseHost)) {
                return response()->json(['message' => 'Invalid target host'], 403);
            }

            // Use Bunny Stream signed URL for HLS proxy
            $targetUrl = $this->applyBunnyStreamSignedUrl($targetUrl, $lesson->video_bunny_id);

            $headers = [];
            if ($userAgent !== '') {
                $headers['User-Agent'] = $userAgent;
            }
            $incomingReferer = (string) $request->header('referer', '');
            $incomingOrigin = (string) $request->header('origin', '');
            $siteUrl = (string) SettingsService::get('site.url', config('app.url'));
            if ($incomingReferer !== '') {
                $headers['Referer'] = $incomingReferer;
            } elseif ($siteUrl !== '') {
                $headers['Referer'] = $siteUrl;
            }
            if ($incomingOrigin !== '') {
                $headers['Origin'] = $incomingOrigin;
            } elseif ($siteUrl !== '') {
                $headers['Origin'] = $siteUrl;
            }

            $client = new Client();
            $upstream = $client->get($targetUrl, [
                'http_errors' => false,
                'timeout' => 15,
                'connect_timeout' => 10,
                'headers' => $headers,
            ]);
            $status = $upstream->getStatusCode();

            if ($status >= 400) {
                return response()->json([
                    'message' => 'Upstream error',
                    'status' => $status,
                    'target_url' => $targetUrl,
                    'base_url' => $baseUrl,
                    'path' => $path,
                    'upstream_body' => substr((string) $upstream->getBody(), 0, 500),
                ], 502);
            }

            $contentType = $upstream->getHeaderLine('Content-Type');
            $isPlaylist = str_contains($contentType, 'application/vnd.apple.mpegurl') || str_contains($contentType, 'application/x-mpegURL') || str_ends_with(strtolower(parse_url($targetUrl, PHP_URL_PATH) ?? ''), '.m3u8');

            if ($isPlaylist) {
                $body = (string) $upstream->getBody();
                $lines = preg_split("/\r?\n/", $body);
                $proxyBase = "/api/backend/lessons/{$id}/hls";
                $rewritten = [];

                foreach ($lines as $line) {
                    if (preg_match('/#EXT-X-KEY:.*URI="([^"]+)"/i', $line, $match)) {
                        $uri = $match[1];
                        $resolvedUri = $this->resolveBunnyUrl($targetUrl, $uri);
                        $sig = $this->signToken((string) $id, $timestamp, $resolvedUri);
                        $newUri = $proxyBase . "?timestamp={$timestamp}&signature={$sig}&path=" . urlencode($resolvedUri);
                        $line = str_replace($uri, $newUri, $line);
                    } elseif (preg_match('/#EXT-X-MAP:.*URI="([^"]+)"/i', $line, $match)) {
                        $uri = $match[1];
                        $resolvedUri = $this->resolveBunnyUrl($targetUrl, $uri);
                        $sig = $this->signToken((string) $id, $timestamp, $resolvedUri);
                        $newUri = $proxyBase . "?timestamp={$timestamp}&signature={$sig}&path=" . urlencode($resolvedUri);
                        $line = str_replace($uri, $newUri, $line);
                    } elseif (str_starts_with(trim($line), '#') || trim($line) === '') {
                        // keep comments and empty lines
                    } else {
                        $uri = trim($line);
                        $resolvedUri = $this->resolveBunnyUrl($targetUrl, $uri);
                        $sig = $this->signToken((string) $id, $timestamp, $resolvedUri);
                        $line = $proxyBase . "?timestamp={$timestamp}&signature={$sig}&path=" . urlencode($resolvedUri);
                    }

                    $rewritten[] = $line;
                }

                return response(implode("\n", $rewritten), 200, [
                    'Content-Type' => 'application/vnd.apple.mpegurl',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Frame-Options' => 'SAMEORIGIN',
                    'Content-Security-Policy' => "frame-ancestors 'self'",
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }

            // Generate challenge token for this response
            $challengeToken = $this->generateChallengeToken($clientIp);

            return response()->stream(function () use ($upstream) {
                $stream = $upstream->getBody();
                while (!$stream->eof()) {
                    echo $stream->read(8192);
                    if (connection_aborted()) {
                        break;
                    }
                }
            }, 200, [
                'Content-Type' => $contentType ?: 'application/octet-stream',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Security-Policy' => "frame-ancestors 'self'",
                'X-Challenge-Token' => $challengeToken,
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Proxy error',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    private function resolveBunnyUrl(string $baseUrl, string $path): string
    {
        if ($path === '') {
            return $baseUrl;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        $port = parse_url($baseUrl, PHP_URL_PORT);
        $basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '/';

        $dir = rtrim(dirname($basePath), '/') . '/';
        $portPart = $port ? ':' . $port : '';

        return $scheme . '://' . $host . $portPart . $dir . ltrim($path, '/');
    }

    /**
     * Apply Bunny Stream signed URL authentication
     * Bunny Stream uses a different token format than CDN Pull Zones
     * Format: SHA256(token_key + video_id + expiration_time) as hex
     */
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

    /**
     * Apply CDN Pull Zone token authentication
     * This is for direct CDN URLs, not for Bunny Stream
     */
    private function applyBunnyTokenAuth(string $url): string
    {
        $enabled = (bool) SettingsService::get('bunnycdn.enable_token_auth', config('bunnycdn.enable_token_auth', false));
        $key = (string) SettingsService::get('bunnycdn.token_auth_key', config('bunnycdn.token_auth_key', ''));
        if (!$enabled || $key === '') {
            return $url;
        }

        $ttl = (int) SettingsService::get('bunnycdn.token_ttl', config('bunnycdn.token_ttl', 3600));
        $ttl = max(60, $ttl);
        $expires = time() + $ttl;

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        // BunnyCDN token auth uses HMAC SHA256 of path + expires
        $hash = hash_hmac('sha256', $path . $expires, $key, true);
        $token = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

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

    private function isDownloadManager(string $userAgent): bool
    {
        $patterns = [
            '/idm\s*\d*|internet\s*download\s*manager/i',
            '/IDM\+\((\d+\.\d+)\)/',
            '/freedownloadmanager|fdm/i',
            '/download\s*master/i',
            '/jdownloader/i',
            '/getright/i',
            '/wget|curl/i',
            '/orbit|eagleget|netants/i',
            '/flashget|thunder|xunlei/i',
            '/download\s*accelerator|dap/i',
            '/manager\/[0-9]+/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    private function getClientIp(Request $request): string
    {
        $forwarded = $request->header('x-forwarded-for');
        if (is_string($forwarded) && $forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }
        return (string) $request->ip();
    }

    private function isAllowedOrigin(string $host, string $referer, string $origin): bool
    {
        // Allow if no referer/origin (direct requests, some players)
        if ($referer === '' && $origin === '') {
            return true;
        }

        // Extract base domain from host (e.g., sonetadmin.cuongdesign.net -> cuongdesign.net)
        $hostParts = explode('.', $host);
        $baseDomain = count($hostParts) >= 2 
            ? $hostParts[count($hostParts) - 2] . '.' . $hostParts[count($hostParts) - 1]
            : $host;

        // Check if referer or origin contains the base domain
        if ($referer !== '' && str_contains($referer, $baseDomain)) {
            return true;
        }
        if ($origin !== '' && str_contains($origin, $baseDomain)) {
            return true;
        }

        // Also allow exact host match
        if ($referer !== '' && str_contains($referer, $host)) {
            return true;
        }
        if ($origin !== '' && str_contains($origin, $host)) {
            return true;
        }

        return false;
    }

    private function verifySignature(string $id, string $timestamp, string $signature, string $path = ''): bool
    {
        $secret = env('VIDEO_SECRET') ?: config('app.key');
        if (is_string($secret) && str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        if (!$secret) {
            return false;
        }

        $message = $id . '|' . $timestamp . '|' . $path;
        $expected = hash_hmac('sha256', $message, $secret);
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $tokenTime = (int) $timestamp;
        if ($tokenTime <= 0) {
            return false;
        }

        $now = (int) (microtime(true) * 1000);
        return ($now - $tokenTime) <= self::SIGNATURE_TTL_MS;
    }

    private function signToken(string $id, string $timestamp, string $path = ''): string
    {
        $secret = env('VIDEO_SECRET') ?: config('app.key');
        if (is_string($secret) && str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $message = $id . '|' . $timestamp . '|' . $path;
        return hash_hmac('sha256', $message, $secret ?: '');
    }

    private function applyRateLimit(string $ip): bool
    {
        $key = 'video_rate:' . $ip;
        $windowMs = self::RATE_LIMIT_WINDOW_MS;
        $maxRequests = self::RATE_LIMIT_MAX_REQUESTS;

        $record = Cache::get($key);
        $now = (int) (microtime(true) * 1000);

        if (!$record || !is_array($record)) {
            Cache::put($key, ['count' => 1, 'resetAt' => $now + $windowMs], 120);
            return true;
        }

        if ($now > ($record['resetAt'] ?? 0)) {
            Cache::put($key, ['count' => 1, 'resetAt' => $now + $windowMs], 120);
            return true;
        }

        if (($record['count'] ?? 0) >= $maxRequests) {
            return false;
        }

        $record['count'] = ($record['count'] ?? 0) + 1;
        Cache::put($key, $record, 120);
        return true;
    }

    private function isBlacklisted(string $ip): bool
    {
        return Cache::has('video_blacklist:' . $ip);
    }

    private function addToBlacklist(string $ip): void
    {
        Cache::put('video_blacklist:' . $ip, true, self::BLACKLIST_DURATION_SECONDS);
    }

    /**
     * Check concurrent sessions to detect download tools
     * Download managers often open many parallel connections
     */
    private function checkConcurrentSessions(string $ip): bool
    {
        $key = 'video_sessions:' . $ip;
        $sessions = Cache::get($key, []);
        $now = time();

        // Clean up expired sessions
        $sessions = array_filter($sessions, fn($ts) => ($now - $ts) < self::SESSION_TTL_SECONDS);

        // Check if too many concurrent sessions
        if (count($sessions) >= self::SESSION_CONCURRENT_LIMIT) {
            return false;
        }

        // Add current session
        $sessions[] = $now;
        Cache::put($key, $sessions, self::SESSION_TTL_SECONDS);

        return true;
    }

    /**
     * Generate challenge token for response
     * This makes it harder for download managers to process responses
     */
    private function generateChallengeToken(string $clientIp): string
    {
        $seed = $clientIp . microtime(true) . mt_rand();
        return substr(hash('sha256', $seed), 0, 32);
    }

    /**
     * Generate chunk token for next chunk request
     * This ensures chunks must be requested in sequence from same client
     */
    private function generateChunkToken(string $clientIp, string $userAgent, int $chunkIndex): string
    {
        $secret = env('VIDEO_SECRET') ?: config('app.key');
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