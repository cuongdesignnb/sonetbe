<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Throwable;

class DiagnoseBunnyVideo extends Command
{
    protected $signature = 'video:bunny-diagnose {--lesson= : Lesson ID to inspect}';

    protected $description = 'Read runtime Bunny playback settings without printing credentials or signed URLs';

    public function handle(): int
    {
        $lessonInput = (string) $this->option('lesson');
        if ($lessonInput === '' || !ctype_digit($lessonInput) || (int) $lessonInput < 1) {
            $this->error('Provide a positive lesson ID with --lesson=<LESSON_ID>.');

            return self::INVALID;
        }

        try {
            $lesson = Lesson::query()
                ->select(['id', 'video_bunny_id', 'video_bunny_library_id'])
                ->find((int) $lessonInput);
        } catch (Throwable) {
            $this->line('BUNNY_DIAGNOSTIC');
            $this->line('APP_ENV=' . $this->safeEnvironment());
            $this->line('LESSON_FOUND=UNKNOWN');
            $this->line('ERROR_CATEGORY=LESSON_LOOKUP_FAILED');

            return self::FAILURE;
        }

        $this->line('BUNNY_DIAGNOSTIC');
        $this->line('APP_ENV=' . $this->safeEnvironment());
        $this->line('LESSON_FOUND=' . ($lesson ? 'YES' : 'NO'));
        $this->line('LESSON_HAS_BUNNY_VIDEO=' . ($this->isPresent($lesson?->video_bunny_id) ? 'YES' : 'NO'));

        $settingsReadable = true;
        $libraryId = $lesson?->video_bunny_library_id;
        if (!$this->isPresent($libraryId)) {
            $libraryId = $this->runtimeSetting('bunnycdn.video_library_id', $settingsReadable);
        }
        $videoApiKey = $this->runtimeSetting('bunnycdn.video_api_key', $settingsReadable);
        $accountApiKey = $this->runtimeSetting('bunnycdn.api_key', $settingsReadable);
        $tokenAuthKey = $this->runtimeSetting('bunnycdn.token_auth_key', $settingsReadable);
        $tokenAuthEnabled = $this->runtimeSetting('bunnycdn.enable_token_auth', $settingsReadable);
        $embedHostname = $this->runtimeSetting('bunnycdn.embed_hostname', $settingsReadable);
        $streamHostname = $this->runtimeSetting('bunnycdn.stream_hostname', $settingsReadable);

        $embedHostname = $this->safeHostname($embedHostname);
        $streamHostname = $this->safeHostname($streamHostname);

        $this->line('LESSON_LIBRARY_ID=' . ($this->isPresent($libraryId) ? 'PRESENT' : 'MISSING'));
        $this->line('');
        $this->line('EMBED_HOSTNAME=' . ($embedHostname ?? 'MISSING_OR_INVALID'));
        $this->line('STREAM_HOSTNAME=' . ($streamHostname ? $this->maskHostname($streamHostname) : 'MISSING_OR_INVALID'));
        $this->line('EMBED_HOSTNAME_VALIDATED=' . ($embedHostname === 'iframe.mediadelivery.net' ? 'PASS' : 'FAIL'));
        $this->line('STREAM_HOSTNAME_FORMAT_VALIDATED=' . ($streamHostname && $streamHostname !== 'iframe.mediadelivery.net' ? 'PASS' : 'FAIL'));
        $this->line('STREAM_HOSTNAME_VALIDATED=UNVERIFIED');
        $this->line('STREAM_PULL_ZONE_MAPPING=UNVERIFIED');
        $this->line('DIRECT_PLAY_VALIDATED=UNVERIFIED');
        $this->line('DRM_POLICY=UNVERIFIED');
        $this->line('TOKEN_AUTH_POLICY=UNVERIFIED');
        $this->line('REFERRER_POLICY=UNVERIFIED');
        $this->line('BUNNY_POLICY_AUDIT=NOT_RUN');
        $this->line('');
        $this->line('BUNNY_API_KEY=' . ($this->isPresent($accountApiKey) ? 'PRESENT' : 'MISSING'));
        $this->line('VIDEO_API_KEY=' . ($this->isPresent($videoApiKey) ? 'PRESENT' : 'MISSING'));
        $this->line('TOKEN_AUTH_KEY=' . ($this->isPresent($tokenAuthKey) ? 'PRESENT' : 'MISSING'));
        $this->line('TOKEN_AUTH_ENABLED=' . $this->formatBoolean($tokenAuthEnabled));
        $this->line('VIDEO_SECRET=' . ($this->isPresent(config('video.video_secret')) ? 'PRESENT' : 'MISSING'));
        $this->line('');
        $this->line('FAILOVER_ENABLED=' . $this->formatBoolean(config('video.failover_enabled', false)));
        $this->line('FORCE_PROXY=' . $this->formatBoolean(config('video.failover_force_proxy', false)));
        $this->line('FAILOVER_TIMEOUT_MS=' . (int) config('video.failover_timeout_ms', 10000));
        $this->line('PLAYBACK_SESSION_TTL=' . (int) config('video.playback_session_ttl', 7200));
        $this->line('RUNTIME_SETTINGS=' . ($settingsReadable ? 'READABLE' : 'CONFIG_FALLBACK'));

        return self::SUCCESS;
    }

    private function runtimeSetting(string $key, bool &$settingsReadable): mixed
    {
        if (!$settingsReadable) {
            return config($key);
        }

        try {
            return SettingsService::get($key, config($key));
        } catch (Throwable) {
            $settingsReadable = false;

            return config($key);
        }
    }

    private function safeEnvironment(): string
    {
        $environment = app()->environment();

        return preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $environment) ?: 'UNKNOWN';
    }

    private function safeHostname(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $hostname = strtolower(rtrim(trim($value), '.'));
        if ($hostname === ''
            || str_contains($hostname, '/')
            || str_contains($hostname, ':')
            || str_contains($hostname, '@')
            || filter_var($hostname, FILTER_VALIDATE_IP)
            || !filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return null;
        }

        return $hostname;
    }

    private function maskHostname(string $hostname): string
    {
        $labels = explode('.', $hostname);
        if (count($labels) < 2) {
            return '***';
        }

        $labels[0] = str_starts_with($labels[0], 'vz-') ? 'vz-********' : '***';

        return implode('.', $labels);
    }

    private function isPresent(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : $value !== null;
    }

    private function formatBoolean(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true
            ? 'true'
            : 'false';
    }
}
