<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware chặn các trình duyệt có tính năng download video tích hợp
 * Chặn từ "vòng gửi xe" - không cho truy cập API video
 */
class BlockDownloadBrowsers
{
    /**
     * Danh sách pattern User-Agent bị chặn
     */
    protected array $blockedPatterns = [
        '/CocCoc/i',
        '/coc_coc_browser/i',
        '/coccoc/i',
        '/savior/i',
        '/FDM/i',
        '/Free Download Manager/i',
        '/IDM/i',
        '/Internet Download Manager/i',
        '/VideoCacheView/i',
        '/Video DownloadHelper/i',
        '/SaveFrom/i',
        '/Y2mate/i',
        '/Xunlei/i',
        '/Thunder/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent', '');

        // Kiểm tra Client Hints (Sec-CH-UA) vì Cốc Cốc có thể fake UA
        if ($this->hasBlockedClientHints($request)) {
            return $this->blockedResponse($request, 'Cốc Cốc');
        }

        // Kiểm tra User-Agent với các pattern bị chặn
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $this->blockedResponse($request, $this->getBlockedBrowserName($pattern));
            }
        }

        // Kiểm tra các header đặc trưng của download manager
        if ($this->hasDownloadManagerHeaders($request)) {
            return $this->blockedResponse($request, 'Download Manager');
        }

        return $next($request);
    }

    /**
     * Kiểm tra các header đặc trưng của download manager
     */
    protected function hasDownloadManagerHeaders(Request $request): bool
    {
        // IDM thường gửi các header đặc biệt
        $suspiciousHeaders = [
            'Range' => '/bytes=\d+-\d+.*bytes=\d+-\d+/', // Multiple range requests
        ];

        // Kiểm tra referer trống với Accept-Ranges header (phổ biến ở download tools)
        if (!$request->header('Referer') && $request->header('Accept-Ranges')) {
            // Có thể là download tool, nhưng cần kiểm tra thêm
            // Không block ngay vì có thể là request hợp lệ
        }

        return false;
    }

    /**
     * Kiểm tra các Client Hints header để phát hiện Cốc Cốc
     */
    protected function hasBlockedClientHints(Request $request): bool
    {
        $hints = [
            $request->header('Sec-CH-UA', ''),
            $request->header('Sec-CH-UA-Full-Version-List', ''),
            $request->header('Sec-CH-UA-Platform', ''),
            $request->header('Sec-CH-UA-Model', ''),
            $request->header('Sec-CH-UA-Arch', ''),
            $request->header('Sec-CH-UA-Bitness', ''),
            $request->header('Sec-CH-UA-WoW64', ''),
        ];

        $stack = implode(' ', array_filter($hints, fn ($value) => is_string($value) && $value !== ''));
        if ($stack === '') {
            return false;
        }

        return stripos($stack, 'coccoc') !== false;
    }

    /**
     * Lấy tên trình duyệt bị chặn từ pattern
     */
    protected function getBlockedBrowserName(string $pattern): string
    {
        $names = [
            '/CocCoc/i' => 'Cốc Cốc',
            '/coc_coc_browser/i' => 'Cốc Cốc',
            '/coccoc/i' => 'Cốc Cốc',
            '/savior/i' => 'Cốc Cốc Savior',
            '/FDM/i' => 'Free Download Manager',
            '/Free Download Manager/i' => 'Free Download Manager',
            '/IDM/i' => 'Internet Download Manager',
            '/Internet Download Manager/i' => 'Internet Download Manager',
            '/VideoCacheView/i' => 'VideoCacheView',
            '/Video DownloadHelper/i' => 'Video DownloadHelper',
            '/SaveFrom/i' => 'SaveFrom',
            '/Y2mate/i' => 'Y2mate',
            '/Xunlei/i' => 'Xunlei',
            '/Thunder/i' => 'Thunder',
        ];

        return $names[$pattern] ?? 'Unknown';
    }

    /**
     * Trả về response khi bị chặn
     */
    protected function blockedResponse(Request $request, string $browserName): Response
    {
        $errorCode = '6007';
        $refHash = strtoupper(substr(md5(time() . $browserName), 0, 12));

        // Log để phân tích
        \Log::warning('Blocked download browser attempt', [
            'browser' => $browserName,
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'error' => true,
            'error_code' => $errorCode,
            'status' => 'BLOCKED',
            'reason' => 'UNTRUSTED_ENVIRONMENT',
            'browser' => $browserName,
            'message' => "Trình duyệt {$browserName} không được hỗ trợ. Vui lòng sử dụng Chrome, Firefox, Safari hoặc Edge.",
            'reference' => "REF-{$refHash}",
            'policy' => 'DRM_PROTECTION_ACTIVE',
            'recommended_browsers' => [
                ['name' => 'Google Chrome', 'url' => 'https://www.google.com/chrome/'],
                ['name' => 'Microsoft Edge', 'url' => 'https://www.microsoft.com/edge'],
                ['name' => 'Mozilla Firefox', 'url' => 'https://www.mozilla.org/firefox/'],
                ['name' => 'Safari', 'url' => 'https://www.apple.com/safari/'],
            ],
        ], 403);
    }
}
