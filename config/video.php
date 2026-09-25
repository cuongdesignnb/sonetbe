<?php

return [
    // Kept in config so legacy HMAC code and read-only diagnostics also work
    // when Laravel's configuration cache is enabled. Never log or print it.
    'video_secret' => env('VIDEO_SECRET', ''),
    'failover_enabled' => filter_var(env('VIDEO_FAILOVER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'failover_timeout_ms' => max(1000, min(30000, (int) env('VIDEO_FAILOVER_TIMEOUT_MS', 10000))),
    'failover_force_proxy' => filter_var(env('VIDEO_FAILOVER_FORCE_PROXY', false), FILTER_VALIDATE_BOOLEAN),
    'playback_session_ttl' => max(300, min(14400, (int) env('VIDEO_PLAYBACK_SESSION_TTL', 7200))),
    'playback_session_max_lifetime' => 14400,
];
