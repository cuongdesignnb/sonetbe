<?php

namespace Tests\Unit\Video;

use App\Services\Video\BunnyHlsRelayService;
use Tests\TestCase;

class BunnyHlsRelayServiceTest extends TestCase
{
    public function test_it_builds_a_playlist_url_only_from_a_valid_stream_hostname(): void
    {
        $service = new BunnyHlsRelayService();

        $this->assertSame(
            'https://vz-example.b-cdn.net/video-abc/playlist.m3u8',
            $service->playlistUrl('vz-example.b-cdn.net', 'video-abc')
        );
        $this->assertTrue($service->resourceBelongsToVideo(
            'https://vz-example.b-cdn.net/video-abc/720p/segment.ts',
            'video-abc'
        ));
        $this->assertFalse($service->resourceBelongsToVideo(
            'https://vz-example.b-cdn.net/video-other/playlist.m3u8',
            'video-abc'
        ));
        foreach ([
            '/video-abc/../video-other/playlist.m3u8',
            '/video-abc/%2e%2e/video-other/playlist.m3u8',
            '/video-abc/%252e%252e/video-other/playlist.m3u8',
            '/video-abc/..%2fvideo-other/playlist.m3u8',
            '/video-abc/%5c..%5cvideo-other/playlist.m3u8',
        ] as $path) {
            $this->assertFalse($service->resourceBelongsToVideo(
                'https://vz-example.b-cdn.net' . $path,
                'video-abc'
            ));
        }
        $this->assertNull($service->playlistUrl('iframe.mediadelivery.net', 'video-abc'));
        $this->assertNull($service->playlistUrl('127.0.0.1', 'video-abc'));
        $this->assertNull($service->playlistUrl('vz-example.b-cdn.net', '../video-abc'));
    }

    public function test_it_rewrites_master_and_media_playlist_resources_to_the_site_relay(): void
    {
        $service = new BunnyHlsRelayService();
        $playlistUrl = 'https://vz-example.b-cdn.net/video-abc/playlist.m3u8';
        $master = "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=1200000\nvariants/720p.m3u8";
        $rewrittenMaster = $service->rewritePlaylist(
            $master,
            $playlistUrl,
            'vz-example.b-cdn.net',
            '/api/backend/lessons/41/hls',
            'opaque-token'
        );

        $this->assertStringContainsString('#EXT-X-STREAM-INF:BANDWIDTH=1200000', $rewrittenMaster);
        $this->assertStringContainsString('/api/backend/lessons/41/hls?', $rewrittenMaster);
        $this->assertStringContainsString('playback_token=opaque-token', $rewrittenMaster);
        $this->assertStringContainsString('variants%2F720p.m3u8', $rewrittenMaster);

        $media = "#EXTM3U\n#EXT-X-KEY:METHOD=AES-128,URI=\"keys/key.bin?accesskey=old-secret\"\n#EXT-X-MAP:URI=\"init.mp4\"\nsegment-001.ts";
        $rewrittenMedia = $service->rewritePlaylist(
            $media,
            'https://vz-example.b-cdn.net/video-abc/720p/index.m3u8',
            'vz-example.b-cdn.net',
            '/api/backend/lessons/41/hls',
            'opaque-token'
        );

        $this->assertStringContainsString('#EXT-X-KEY:METHOD=AES-128,URI="/api/backend/lessons/41/hls?', $rewrittenMedia);
        $this->assertStringContainsString('#EXT-X-MAP:URI="/api/backend/lessons/41/hls?', $rewrittenMedia);
        $this->assertStringContainsString('segment-001.ts', $rewrittenMedia);
        $this->assertStringNotContainsString('old-secret', $rewrittenMedia);
        $this->assertStringNotContainsString('vz-example.b-cdn.net', $rewrittenMedia);
    }

    public function test_it_rejects_external_or_non_https_resources_in_playlists(): void
    {
        $service = new BunnyHlsRelayService();
        $playlistUrl = 'https://vz-example.b-cdn.net/video-abc/playlist.m3u8';

        $this->assertNull($service->rewritePlaylist(
            "#EXTM3U\nhttps://attacker.example/evil.ts",
            $playlistUrl,
            'vz-example.b-cdn.net',
            '/api/backend/lessons/41/hls',
            'opaque-token'
        ));
        $this->assertNull($service->rewritePlaylist(
            "#EXTM3U\n#EXT-X-KEY:METHOD=AES-128,URI=\"file:///etc/passwd\"",
            $playlistUrl,
            'vz-example.b-cdn.net',
            '/api/backend/lessons/41/hls',
            'opaque-token'
        ));
    }
}
