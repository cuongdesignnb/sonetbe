<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Cache;

class PlaybackSessionService
{
    private const TOKEN_PREFIX = 'video_playback:';
    private const TELEMETRY_PREFIX = 'video_playback_telemetry:';

    /** @return array{token:string,session_id:string,expires_at:int} */
    public function issue(int $lessonId, ?int $userId, bool $preview, ?string $streamHostname = null): array
    {
        $now = time();
        $ttl = (int) config('video.playback_session_ttl', 7200);
        $maxLifetime = (int) config('video.playback_session_max_lifetime', 14400);
        $expiresAt = $now + max(300, min(14400, $ttl));
        $hardExpiresAt = $now + max(300, min(14400, $maxLifetime));
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $token);
        $sessionId = bin2hex(random_bytes(8));
        $record = [
            'lesson_id' => $lessonId,
            'user_id' => $preview ? null : $userId,
            'preview' => $preview,
            'created_at' => $now,
            'expires_at' => min($expiresAt, $hardExpiresAt),
            'hard_expires_at' => $hardExpiresAt,
            'session_id' => $sessionId,
            'stream_hostname' => $streamHostname !== null ? strtolower(trim($streamHostname)) : null,
        ];
        $remaining = max(1, $record['expires_at'] - $now);

        Cache::put(self::TOKEN_PREFIX . $tokenHash, $record, $remaining);
        Cache::put(self::TELEMETRY_PREFIX . $sessionId, [
            'lesson_id' => $lessonId,
            'token_hash' => $tokenHash,
            'session_id' => $sessionId,
            'created_at' => $now,
            'expires_at' => $record['expires_at'],
        ], $remaining);

        return [
            'token' => $token,
            'session_id' => $sessionId,
            'expires_at' => $record['expires_at'],
        ];
    }

    /**
     * Resolves a bearer token without ever storing the raw token. A valid request
     * slides the idle expiry but never beyond the original four-hour ceiling.
     */
    public function resolve(string $token, int $lessonId): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{40,48}$/', $token)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $key = self::TOKEN_PREFIX . $tokenHash;
        $record = Cache::get($key);
        $now = time();

        if (!is_array($record)
            || (int) ($record['lesson_id'] ?? 0) !== $lessonId
            || (int) ($record['expires_at'] ?? 0) <= $now
            || (int) ($record['hard_expires_at'] ?? 0) <= $now) {
            return null;
        }

        return $record;
    }

    /** Refresh an authorized session after lesson entitlement has been rechecked. */
    public function touch(string $token, array $record): void
    {
        $now = time();
        $tokenHash = hash('sha256', $token);
        $slidingExpiry = min(
            $now + max(300, min(14400, (int) config('video.playback_session_ttl', 7200))),
            (int) ($record['hard_expires_at'] ?? 0)
        );
        if ($slidingExpiry <= $now) {
            return;
        }

        $record['expires_at'] = $slidingExpiry;
        $remaining = max(1, $slidingExpiry - $now);
        Cache::put(self::TOKEN_PREFIX . $tokenHash, $record, $remaining);

        $sessionId = (string) ($record['session_id'] ?? '');
        if ($sessionId !== '') {
            Cache::put(self::TELEMETRY_PREFIX . $sessionId, [
                'lesson_id' => (int) ($record['lesson_id'] ?? 0),
                'token_hash' => $tokenHash,
                'session_id' => $sessionId,
                'created_at' => (int) ($record['created_at'] ?? $now),
                'expires_at' => $slidingExpiry,
            ], $remaining);
        }
    }

    public function resolveTelemetrySession(string $sessionId, int $lessonId): ?array
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $sessionId)) {
            return null;
        }

        $record = Cache::get(self::TELEMETRY_PREFIX . $sessionId);
        if (!is_array($record)
            || (int) ($record['lesson_id'] ?? 0) !== $lessonId
            || (int) ($record['expires_at'] ?? 0) <= time()) {
            return null;
        }

        return $record;
    }
}
