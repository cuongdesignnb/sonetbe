<?php

namespace App\Services\Video;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class BunnyHlsRelayService
{
    public function __construct(private ?ClientInterface $httpClient = null)
    {
    }

    public function upstreamClient(): ClientInterface
    {
        return $this->httpClient ?? new Client();
    }

    public function playlistUrl(string $hostname, string $videoId): ?string
    {
        $hostname = $this->normalizeHostname($hostname);
        if ($hostname === null || !$this->isValidVideoId($videoId)) {
            return null;
        }

        return 'https://' . $hostname . '/' . rawurlencode($videoId) . '/playlist.m3u8';
    }

    public function resourceBelongsToVideo(string $resourceUrl, string $videoId): bool
    {
        $path = parse_url($resourceUrl, PHP_URL_PATH);
        if (!is_string($path)
            || !$this->isValidVideoId($videoId)
            || str_contains($path, '\\')
            || preg_match('/[\x00-\x20\x7f]/', $path)
            // Encoded separators, dots, and percent signs can become path
            // delimiters after one or more decoding passes at the CDN edge.
            || preg_match('/%(?:2f|5c|2e|25)/i', $path)) {
            return false;
        }

        $decodedPath = rawurldecode($path);
        foreach (explode('/', $decodedPath) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return false;
            }
        }

        return str_starts_with($decodedPath, '/' . $videoId . '/');
    }

    /** Resolve an HLS URI and reject any host outside the configured Stream Pull Zone. */
    public function resolveResource(string $playlistUrl, string $resource, string $allowedHostname): ?string
    {
        if (preg_match('/[\x00-\x20\x7f]/', $resource)) {
            return null;
        }

        try {
            $resolved = UriResolver::resolve(new Uri($playlistUrl), new Uri($resource));
            $host = strtolower($resolved->getHost());
            $allowed = $this->normalizeHostname($allowedHostname);

            if ($allowed === null
                || strtolower($resolved->getScheme()) !== 'https'
                || $host !== $allowed
                || $resolved->getUserInfo() !== ''
                || ($resolved->getPort() !== null && $resolved->getPort() !== 443)) {
                return null;
            }

            return (string) $resolved
                ->withFragment('')
                ->withQuery($this->stripSensitiveQuery($resolved->getQuery()));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rewrite HLS URI lines and URI attributes (including key/map/variant tags). */
    public function rewritePlaylist(string $body, string $playlistUrl, string $allowedHostname, string $proxyBase, string $playbackToken): ?string
    {
        $rewritten = [];
        foreach (preg_split('/\r?\n/', $body) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $rewritten[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                $invalidUriAttribute = false;
                $newLine = preg_replace_callback('/\bURI=("|\')(.*?)\1/i', function (array $match) use ($playlistUrl, $allowedHostname, $proxyBase, $playbackToken, &$invalidUriAttribute): string {
                    $local = $this->localResourceUrl($match[2], $playlistUrl, $allowedHostname, $proxyBase, $playbackToken);
                    if ($local === null) {
                        $invalidUriAttribute = true;
                        return $match[0];
                    }
                    return 'URI=' . $match[1] . $local . $match[1];
                }, $line);

                // URI-bearing HLS tags must not leave Bunny URLs in the playlist if invalid.
                if ($newLine === null || $invalidUriAttribute || preg_match('/\bURI=("|\')(?:https?:)?\/\//i', $newLine)) {
                    return null;
                }
                $rewritten[] = $newLine;
                continue;
            }

            $local = $this->localResourceUrl($trimmed, $playlistUrl, $allowedHostname, $proxyBase, $playbackToken);
            if ($local === null) {
                return null;
            }
            $rewritten[] = $local;
        }

        return implode("\n", $rewritten);
    }

    private function localResourceUrl(string $resource, string $playlistUrl, string $allowedHostname, string $proxyBase, string $playbackToken): ?string
    {
        $target = $this->resolveResource($playlistUrl, $resource, $allowedHostname);
        if ($target === null) {
            return null;
        }

        $targetUri = new Uri($target);
        $upstreamPath = $targetUri->getPath();
        if ($targetUri->getQuery() !== '') {
            $upstreamPath .= '?' . $targetUri->getQuery();
        }

        return $proxyBase . '?' . http_build_query([
            'playback_token' => $playbackToken,
            'path' => $upstreamPath,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function normalizeHostname(string $hostname): ?string
    {
        $hostname = strtolower(trim($hostname));
        if ($hostname === '' || str_contains($hostname, '://') || str_contains($hostname, '/')) {
            return null;
        }

        $hostname = rtrim($hostname, '.');
        if ($hostname === 'iframe.mediadelivery.net'
            || filter_var($hostname, FILTER_VALIDATE_IP)
            || !filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return null;
        }

        return $hostname;
    }

    private function isValidVideoId(string $videoId): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{1,128}$/', $videoId) === 1;
    }

    private function stripSensitiveQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $params = [];
        parse_str($query, $params);
        foreach (array_keys($params) as $key) {
            if (preg_match('/^(token|expires|signature|playback_token|accesskey)$/i', (string) $key)) {
                unset($params[$key]);
            }
        }

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
