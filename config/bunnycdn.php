<?php

return [
    'api_key' => env('BUNNY_CDN_API_KEY', ''),
    'storage_zone_name' => env('BUNNY_CDN_STORAGE_ZONE_NAME', ''),
    'pull_zone_url' => env('BUNNY_CDN_PULL_ZONE_URL', ''),
    
    // Video streaming settings
    'video_library_id' => env('BUNNY_CDN_VIDEO_LIBRARY_ID', ''),
    'video_api_key' => env('BUNNY_CDN_VIDEO_API_KEY', ''),
    
    // Stream settings
    'stream_hostname' => env('BUNNY_CDN_STREAM_HOSTNAME', 'iframe.mediadelivery.net'),
    
    // Upload settings
    'max_file_size' => 1073741824, // 1GB in bytes
    'allowed_extensions' => ['mp4', 'mov', 'avi', 'wmv', 'mkv'],
    
    // Security settings
    'token_auth_key' => env('BUNNY_CDN_TOKEN_AUTH_KEY', ''),
    'enable_token_auth' => env('BUNNY_CDN_ENABLE_TOKEN_AUTH', false),
    'token_ttl' => env('BUNNY_CDN_TOKEN_TTL', 3600),
];