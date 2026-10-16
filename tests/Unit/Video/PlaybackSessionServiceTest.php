<?php

namespace Tests\Unit\Video;

use App\Services\Video\PlaybackSessionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlaybackSessionServiceTest extends TestCase
{
    public function test_it_issues_a_random_token_and_stores_only_its_hash(): void
    {
        $service = app(PlaybackSessionService::class);
        $issued = $service->issue(41, 17, false);
        $record = Cache::get('video_playback:' . hash('sha256', $issued['token']));

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $issued['token']);
        $this->assertIsArray($record);
        $this->assertSame(41, $record['lesson_id']);
        $this->assertSame(17, $record['user_id']);
        $this->assertFalse($record['preview']);
        $this->assertArrayNotHasKey('token', $record);
        $this->assertSame($record, $service->resolve($issued['token'], 41));
    }

    public function test_it_rejects_tokens_for_another_lesson_and_expired_tokens(): void
    {
        $service = app(PlaybackSessionService::class);
        $issued = $service->issue(41, 17, false);

        $this->assertNull($service->resolve($issued['token'], 42));

        $key = 'video_playback:' . hash('sha256', $issued['token']);
        $record = Cache::get($key);
        $record['expires_at'] = time() - 1;
        Cache::put($key, $record, 60);

        $this->assertNull($service->resolve($issued['token'], 41));
    }

    public function test_preview_sessions_have_no_user_binding_and_expire_by_absolute_limit(): void
    {
        config(['video.playback_session_ttl' => 7200, 'video.playback_session_max_lifetime' => 14400]);
        $service = app(PlaybackSessionService::class);
        $issued = $service->issue(41, 999, true);
        $key = 'video_playback:' . hash('sha256', $issued['token']);
        $record = Cache::get($key);

        $this->assertTrue($record['preview']);
        $this->assertNull($record['user_id']);

        $record['hard_expires_at'] = time() - 1;
        Cache::put($key, $record, 60);
        $service->touch($issued['token'], $record);

        $this->assertNull($service->resolve($issued['token'], 41));
    }
}
