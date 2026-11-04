<?php

namespace Tests\Feature\Video;

use App\Http\Controllers\LessonController;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Video\BunnyHlsRelayService;
use App\Services\Video\PlaybackSessionService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BunnyPlaybackFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'video.failover_enabled' => true,
            'video.failover_timeout_ms' => 10000,
            'video.failover_force_proxy' => false,
            'video.playback_session_ttl' => 7200,
            'bunnycdn.embed_hostname' => 'iframe.mediadelivery.net',
            'bunnycdn.stream_hostname' => 'vz-example.b-cdn.net',
            'bunnycdn.enable_token_auth' => false,
            'bunnycdn.token_auth_key' => '',
            'bunnycdn.video_library_id' => '',
        ]);
    }

    public function test_paid_video_issues_a_session_only_after_lesson_authorization(): void
    {
        $lesson = $this->createLesson(100, false);

        $sessions = Mockery::mock(PlaybackSessionService::class);
        $sessions->shouldNotReceive('issue');
        $this->app->instance(PlaybackSessionService::class, $sessions);

        $denied = (new LessonController())->streamVideo($lesson->id);
        $this->assertSame(401, $denied->getStatusCode());

        $user = $this->createUser();
        $this->enroll($user, 100);
        Auth::guard('web')->setUser($user);
        $this->app->forgetInstance(PlaybackSessionService::class);

        $response = (new LessonController())->streamVideo($lesson->id);
        $data = $response->getData(true);
        parse_str((string) parse_url($data['fallback']['url'], PHP_URL_QUERY), $query);
        $session = app(PlaybackSessionService::class)->resolve($query['playback_token'], $lesson->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bunny_embed', $data['primary']['type']);
        $this->assertSame('hls_proxy', $data['fallback']['type']);
        $this->assertStringStartsWith('https://iframe.mediadelivery.net/embed/', $data['primary']['url']);
        $this->assertSame($user->id, $session['user_id']);
        $this->assertFalse($session['preview']);
    }

    public function test_preview_endpoint_issues_a_preview_bound_session_and_rejects_non_preview_lessons(): void
    {
        $regularLesson = $this->createLesson(101, false);
        $previewLesson = $this->createLesson(102, true);

        $sessions = Mockery::mock(PlaybackSessionService::class);
        $sessions->shouldNotReceive('issue');
        $this->app->instance(PlaybackSessionService::class, $sessions);
        $this->assertSame(403, (new LessonController())->streamPreviewVideo($regularLesson->id)->getStatusCode());

        $this->app->forgetInstance(PlaybackSessionService::class);
        $response = (new LessonController())->streamPreviewVideo($previewLesson->id);
        $data = $response->getData(true);
        parse_str((string) parse_url($data['fallback']['url'], PHP_URL_QUERY), $query);
        $session = app(PlaybackSessionService::class)->resolve($query['playback_token'], $previewLesson->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($session['preview']);
        $this->assertNull($session['user_id']);
    }

    public function test_proxy_rejects_lesson_mismatch_expiry_and_revoked_entitlement(): void
    {
        $lessonA = $this->createLesson(103, false);
        $lessonB = $this->createLesson(104, false);
        $user = $this->createUser();
        $this->enroll($user, 103);
        $service = app(PlaybackSessionService::class);

        $forA = $service->issue($lessonA->id, $user->id, false);
        $mismatch = $this->proxyRequest($lessonB->id, $forA['token']);
        $this->assertSame(403, $mismatch->getStatusCode());

        $sameRouteDifferentVideo = $this->proxyRequest(
            $lessonA->id,
            $forA['token'],
            '/video-' . $lessonB->course_id . '/playlist.m3u8'
        );
        $this->assertSame(403, $sameRouteDifferentVideo->getStatusCode());

        $expired = $service->issue($lessonA->id, $user->id, false);
        $key = 'video_playback:' . hash('sha256', $expired['token']);
        $record = Cache::get($key);
        $record['expires_at'] = time() - 1;
        Cache::put($key, $record, 60);
        $expiredResponse = $this->proxyRequest($lessonA->id, $expired['token']);
        $this->assertSame(403, $expiredResponse->getStatusCode());

        $revoked = $service->issue($lessonA->id, $user->id, false);
        DB::table('enrollments')->where('user_id', $user->id)->where('course_id', 103)->delete();
        $revokedResponse = $this->proxyRequest($lessonA->id, $revoked['token']);
        $this->assertSame(403, $revokedResponse->getStatusCode());
    }

    public function test_preview_proxy_session_is_invalidated_when_the_lesson_stops_being_preview(): void
    {
        $lesson = $this->createLesson(105, true);
        $issued = app(PlaybackSessionService::class)->issue($lesson->id, null, true);
        $lesson->is_preview = false;
        $lesson->save();

        $response = $this->proxyRequest($lesson->id, $issued['token']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_disabling_failover_stops_existing_relay_sessions_immediately(): void
    {
        $lesson = $this->createLesson(109, true);
        $issued = app(PlaybackSessionService::class)->issue($lesson->id, null, true);
        config(['video.failover_enabled' => false]);

        $response = $this->proxyRequest($lesson->id, $issued['token']);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Video relay temporarily unavailable', $response->getData(true)['message']);
    }

    public function test_proxy_streams_segments_and_forwards_range_without_buffering_the_file(): void
    {
        $lesson = $this->createLesson(106, false);
        $user = $this->createUser();
        $this->enroll($user, 106);
        $issued = app(PlaybackSessionService::class)->issue($lesson->id, $user->id, false);
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new GuzzleResponse(206, [
                'Content-Type' => 'video/mp2t',
                'Content-Length' => '3',
                'Content-Range' => 'bytes 0-2/3',
                'Accept-Ranges' => 'bytes',
            ], 'abc'),
        ]));
        $stack->push(Middleware::history($history));
        $this->app->instance(BunnyHlsRelayService::class, new BunnyHlsRelayService(new Client(['handler' => $stack])));

        $request = Request::create('/api/lessons/' . $lesson->id . '/hls', 'GET', [
            'playback_token' => $issued['token'],
            'path' => '/video-106/segment.ts',
        ], [], [], ['HTTP_RANGE' => 'bytes=0-2']);
        $response = (new LessonController())->proxyHls($request, $lesson->id);
        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('abc', $body);
        $this->assertSame('bytes=0-2', $history[0]['request']->getHeaderLine('Range'));
        $this->assertSame('bytes 0-2/3', $response->headers->get('Content-Range'));
    }

    public function test_upstream_errors_and_timeouts_return_redacted_responses(): void
    {
        $lesson = $this->createLesson(107, false);
        $user = $this->createUser();
        $this->enroll($user, 107);
        $issued = app(PlaybackSessionService::class)->issue($lesson->id, $user->id, false);

        $this->app->instance(BunnyHlsRelayService::class, new BunnyHlsRelayService(new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new GuzzleResponse(403, [], 'upstream signed token=upstream-secret'),
            ])),
        ])));
        $forbidden = $this->proxyRequest($lesson->id, $issued['token']);
        $this->assertSame(502, $forbidden->getStatusCode());
        $this->assertStringNotContainsString('upstream-secret', (string) $forbidden->getContent());
        $this->assertStringNotContainsString('playback_token', (string) $forbidden->getContent());

        $this->app->forgetInstance(BunnyHlsRelayService::class);
        $exception = new ConnectException(
            'connect timeout for https://vz-example.b-cdn.net/playlist.m3u8?token=upstream-secret',
            new GuzzleRequest('GET', 'https://vz-example.b-cdn.net/playlist.m3u8')
        );
        $this->app->instance(BunnyHlsRelayService::class, new BunnyHlsRelayService(new Client([
            'handler' => HandlerStack::create(new MockHandler([$exception, $exception])),
        ])));
        $timeout = $this->proxyRequest($lesson->id, $issued['token']);
        $this->assertSame(502, $timeout->getStatusCode());
        $this->assertStringNotContainsString('upstream-secret', (string) $timeout->getContent());
        $this->assertStringNotContainsString('vz-example.b-cdn.net', (string) $timeout->getContent());
    }

    public function test_rate_limit_is_scoped_to_a_playback_session_and_returns_retry_after(): void
    {
        $lesson = $this->createLesson(108, false);
        $user = $this->createUser();
        $this->enroll($user, 108);
        $issued = app(PlaybackSessionService::class)->issue($lesson->id, $user->id, false);
        for ($attempt = 0; $attempt < 900; $attempt++) {
            \Illuminate\Support\Facades\RateLimiter::hit('video-relay:session:' . $issued['session_id'], 60);
        }

        $response = $this->proxyRequest($lesson->id, $issued['token']);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertNotEmpty($response->headers->get('Retry-After'));
        $this->assertStringNotContainsString('blacklist', strtolower((string) $response->getContent()));
    }

    private function proxyRequest(int $lessonId, string $token, ?string $path = null)
    {
        $query = ['playback_token' => $token];
        if ($path !== null) {
            $query['path'] = $path;
        }

        return (new LessonController())->proxyHls(
            Request::create('/api/lessons/' . $lessonId . '/hls', 'GET', $query),
            $lessonId
        );
    }

    private function createLesson(int $courseId, bool $preview): Lesson
    {
        return Lesson::query()->create([
            'course_id' => $courseId,
            'title' => 'Lesson ' . $courseId,
            'video_bunny_id' => 'video-' . $courseId,
            'video_bunny_library_id' => 'library-1',
            'is_preview' => $preview,
        ]);
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Student',
            'email' => uniqid('student-', true) . '@example.test',
            'password' => 'not-used',
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    private function enroll(User $user, int $courseId): void
    {
        DB::table('enrollments')->insert([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
