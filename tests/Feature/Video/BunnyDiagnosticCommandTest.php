<?php

namespace Tests\Feature\Video;

use App\Models\Lesson;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BunnyDiagnosticCommandTest extends TestCase
{
    public function test_it_reports_only_redacted_bunny_runtime_values(): void
    {
        $lesson = Lesson::query()->create([
            'course_id' => 501,
            'title' => 'Diagnostic lesson',
            'video_bunny_id' => 'video-diagnostic-secret-id',
            'video_bunny_library_id' => 'library-diagnostic-secret-id',
            'is_preview' => true,
        ]);

        config([
            'bunnycdn.api_key' => 'account-api-secret-sample',
            'bunnycdn.video_api_key' => 'video-api-secret-sample',
            'bunnycdn.token_auth_key' => 'token-auth-secret-sample',
            'bunnycdn.enable_token_auth' => true,
            'bunnycdn.embed_hostname' => 'iframe.mediadelivery.net',
            'bunnycdn.stream_hostname' => 'vz-private-pullzone.b-cdn.net',
            'video.video_secret' => 'video-secret-sample',
        ]);

        $status = Artisan::call('video:bunny-diagnose', ['--lesson' => (string) $lesson->id]);
        $output = Artisan::output();

        $this->assertSame(0, $status);
        $this->assertStringContainsString('APP_ENV=testing', $output);
        $this->assertStringContainsString('LESSON_FOUND=YES', $output);
        $this->assertStringContainsString('LESSON_HAS_BUNNY_VIDEO=YES', $output);
        $this->assertStringContainsString('LESSON_LIBRARY_ID=PRESENT', $output);
        $this->assertStringContainsString('EMBED_HOSTNAME=iframe.mediadelivery.net', $output);
        $this->assertStringContainsString('STREAM_HOSTNAME=vz-********.b-cdn.net', $output);
        $this->assertStringContainsString('TOKEN_AUTH_ENABLED=true', $output);
        $this->assertStringContainsString('STREAM_HOSTNAME_VALIDATED=UNVERIFIED', $output);

        foreach ([
            'account-api-secret-sample',
            'video-api-secret-sample',
            'token-auth-secret-sample',
            'video-secret-sample',
            'vz-private-pullzone',
            'video-diagnostic-secret-id',
            'library-diagnostic-secret-id',
        ] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $output);
        }

        $this->assertSame('video-diagnostic-secret-id', $lesson->fresh()->video_bunny_id);
    }
}
