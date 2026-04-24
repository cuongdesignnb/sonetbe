<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBunnyController extends Controller
{
    public function libraries()
    {
        $apiKey = trim((string) SettingsService::get('bunnycdn.api_key', config('bunnycdn.api_key')));
        if (!$apiKey) {
            return response()->json([
                'message' => 'Bunny API Key chưa được cấu hình. Vui lòng điền API Key (Account level) tại Cài đặt → Bunny Storage → API Key.',
            ], 422);
        }

        $client = new Client(['timeout' => 15, 'connect_timeout' => 10]);

        try {
            $res = $client->get('https://api.bunny.net/videolibrary', [
                'headers' => [
                    'AccessKey' => $apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'page' => 1,
                    'perPage' => 1000,
                    'includeAccessKey' => 'true',
                ],
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $body = (string) ($e->getResponse()?->getBody() ?? '');
            Log::error("Bunny libraries ClientException: status={$status} body={$body}");

            $hint = $status === 401
                ? ' API Key không hợp lệ. Kiểm tra lại Account API Key tại Bunny dashboard → Account → API Keys.'
                : '';

            return response()->json([
                'message' => "Bunny API lỗi (HTTP {$status}).{$hint}",
                'details' => $body,
                'status' => $status,
            ], $status);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $body = (string) ($e->getResponse()?->getBody() ?? '');
            Log::error("Bunny libraries ServerException: status={$status} body={$body}");

            return response()->json([
                'message' => "Bunny API server lỗi (HTTP {$status}). Vui lòng thử lại sau.",
                'details' => $body,
            ], 502);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("Bunny libraries ConnectException: " . $e->getMessage());

            return response()->json([
                'message' => 'Không thể kết nối tới Bunny API. Kiểm tra kết nối mạng của server.',
            ], 502);
        } catch (\Exception $e) {
            Log::error("Bunny libraries Exception: " . $e->getMessage());

            return response()->json([
                'message' => 'Lỗi không xác định khi gọi Bunny API: ' . $e->getMessage(),
            ], 500);
        }

        $payload = json_decode((string) $res->getBody(), true);

        // Bunny API returns paginated response: { TotalItems, Items: [...] }
        // Extract the Items array; also handle legacy flat-array format
        $libraries = [];
        if (is_array($payload)) {
            if (isset($payload['Items'])) {
                $libraries = $payload['Items'];
            } elseif (isset($payload['items'])) {
                $libraries = $payload['items'];
            } elseif (array_values($payload) === $payload) {
                // Flat array (legacy format)
                $libraries = $payload;
            }
        }

        // Normalize PascalCase keys from Bunny API to lowercase for frontend
        $libraries = array_map(function ($lib) {
            return [
                'id' => $lib['Id'] ?? $lib['id'] ?? null,
                'name' => $lib['Name'] ?? $lib['name'] ?? '',
                'videoCount' => $lib['VideoCount'] ?? $lib['videoCount'] ?? 0,
                'pullZoneId' => $lib['PullZoneId'] ?? $lib['pullZoneId'] ?? null,
                'storageZoneId' => $lib['StorageZoneId'] ?? $lib['storageZoneId'] ?? null,
                'apiKey' => $lib['ApiKey'] ?? $lib['apiKey'] ?? null,
            ];
        }, $libraries);

        // Fetch CDN hostname for each library via its Pull Zone
        foreach ($libraries as &$lib) {
            $lib['cdnHostname'] = null;
            if (!empty($lib['pullZoneId']) && $apiKey) {
                $lib['cdnHostname'] = $this->fetchPullZoneHostname($apiKey, $lib['pullZoneId']);
            }
        }
        unset($lib);

        // Also return the configured video_library_id so frontend can pre-select
        $configuredLibraryId = SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));

        return response()->json([
            'libraries' => $libraries,
            'configured_library_id' => $configuredLibraryId ? (int) $configuredLibraryId : null,
        ]);
    }

    public function videos(Request $request, $libraryId)
    {
        // Try the configured video_api_key first, then fetch library-specific key
        $apiKey = trim((string) SettingsService::get('bunnycdn.video_api_key', config('bunnycdn.video_api_key')));
        $accountApiKey = trim((string) SettingsService::get('bunnycdn.api_key', config('bunnycdn.api_key')));
        $cdnHostname = null;

        // If no video_api_key is set, or if the library differs from the configured one,
        // fetch the library-specific API key + CDN hostname using the Account API Key
        $configuredLibraryId = SettingsService::get('bunnycdn.video_library_id', config('bunnycdn.video_library_id'));
        if ($accountApiKey) {
            $details = $this->fetchLibraryDetails($accountApiKey, $libraryId);
            $cdnHostname = $details['cdnHostname'];
            if (!$apiKey || (string) $libraryId !== (string) $configuredLibraryId) {
                $apiKey = $details['apiKey'];
            }
        }

        if (!$apiKey) {
            return response()->json([
                'message' => 'Bunny Video API Key chưa được cấu hình. Vui lòng điền tại Cài đặt → Bunny Stream → Video API Key.',
            ], 422);
        }

        $page = max(1, (int) $request->query('page', 1));
        $itemsPerPage = min(100, max(1, (int) $request->query('itemsPerPage', 30)));
        $search = trim((string) $request->query('search', ''));
        $orderBy = $request->query('orderBy', 'date');

        $query = [
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'orderBy' => $orderBy,
        ];

        if ($search !== '') {
            $query['search'] = $search;
        }

        $client = new Client(['timeout' => 15, 'connect_timeout' => 10]);
        try {
            $res = $client->get("https://video.bunnycdn.com/library/{$libraryId}/videos", [
                'headers' => [
                    'AccessKey' => $apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $body = (string) ($e->getResponse()?->getBody() ?? '');
            Log::error("Bunny videos ClientException: libraryId={$libraryId} status={$status} body={$body}");

            $hint = $status === 401
                ? ' Video API Key không khớp với Library ID đang chọn. Hãy kiểm tra Video API Key trong settings khớp với library này.'
                : '';

            return response()->json([
                'message' => "Bunny Stream API lỗi (HTTP {$status}).{$hint}",
                'details' => $body,
                'status' => $status,
            ], $status);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $body = (string) ($e->getResponse()?->getBody() ?? '');
            Log::error("Bunny videos ServerException: libraryId={$libraryId} status={$status} body={$body}");

            return response()->json([
                'message' => "Bunny Stream server lỗi (HTTP {$status}). Thử lại sau.",
            ], 502);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("Bunny videos ConnectException: " . $e->getMessage());

            return response()->json([
                'message' => 'Không thể kết nối tới Bunny Stream API.',
            ], 502);
        } catch (\Exception $e) {
            Log::error("Bunny videos Exception: " . $e->getMessage());

            return response()->json([
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }

        $payload = json_decode((string) $res->getBody(), true);

        return response()->json([
            'videos' => $payload ?? [],
            'cdn_hostname' => $cdnHostname,
        ]);
    }

    /**
     * Fetch library details (API key + CDN hostname) from Bunny API.
     */
    private function fetchLibraryDetails(string $accountApiKey, $libraryId): array
    {
        try {
            $client = new Client(['timeout' => 10, 'connect_timeout' => 5]);
            $res = $client->get("https://api.bunny.net/videolibrary/{$libraryId}", [
                'headers' => [
                    'AccessKey' => $accountApiKey,
                    'Accept' => 'application/json',
                ],
                'query' => ['includeAccessKey' => 'true'],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $pullZoneId = $data['PullZoneId'] ?? $data['pullZoneId'] ?? null;
            $cdnHostname = null;
            if ($pullZoneId) {
                $cdnHostname = $this->fetchPullZoneHostname($accountApiKey, $pullZoneId);
            }
            return [
                'apiKey' => $data['ApiKey'] ?? $data['apiKey'] ?? null,
                'cdnHostname' => $cdnHostname,
            ];
        } catch (\Exception $e) {
            Log::error("Bunny fetchLibraryDetails error: libraryId={$libraryId} " . $e->getMessage());
            return ['apiKey' => null, 'cdnHostname' => null];
        }
    }

    /**
     * Fetch the system hostname of a Bunny Pull Zone.
     */
    private function fetchPullZoneHostname(string $accountApiKey, $pullZoneId): ?string
    {
        try {
            $client = new Client(['timeout' => 10, 'connect_timeout' => 5]);
            $res = $client->get("https://api.bunny.net/pullzone/{$pullZoneId}", [
                'headers' => [
                    'AccessKey' => $accountApiKey,
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $hostnames = $data['Hostnames'] ?? $data['hostnames'] ?? [];
            // Prefer the system hostname (*.b-cdn.net)
            foreach ($hostnames as $h) {
                if (!empty($h['IsSystemHostname']) || !empty($h['isSystemHostname'])) {
                    return $h['Value'] ?? $h['value'] ?? null;
                }
            }
            // Fallback to first hostname
            if (!empty($hostnames[0])) {
                return $hostnames[0]['Value'] ?? $hostnames[0]['value'] ?? null;
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Bunny fetchPullZoneHostname error: pullZoneId={$pullZoneId} " . $e->getMessage());
            return null;
        }
    }
}
