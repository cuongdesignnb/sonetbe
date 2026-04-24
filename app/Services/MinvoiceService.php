<?php

namespace App\Services;

use App\Models\InvoiceRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MinvoiceService
{
    public function isEnabled(): bool
    {
        return (bool) config('minvoice.enabled')
            && filled(config('minvoice.base_url'))
            && filled(config('minvoice.username'))
            && filled(config('minvoice.password'))
            && filled(config('minvoice.tax_code'));
    }

    public function issueInvoice(InvoiceRequest $invoiceRequest): array
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Minvoice chưa được cấu hình đầy đủ.');
        }

        $invoiceRequest->loadMissing(['payment.user', 'payment.course', 'payment.ebook', 'payment.webinar']);

        $series = $this->resolveInvoiceSeries();
        $payload = $this->buildInvoicePayload($invoiceRequest, $series['value']);
        $response = $this->request('post', '/api/InvoiceApi78/SaveSign', [
            'json' => $payload,
        ]);

        $body = $response->json();

        if (($body['ok'] ?? false) !== true || ($body['code'] ?? null) !== '00') {
            throw new RuntimeException($body['message'] ?? $body['error'] ?? 'Minvoice trả về lỗi phát hành hóa đơn.');
        }

        $data = $body['data'] ?? [];

        return [
            'request_payload' => $payload,
            'response_payload' => $body,
            'provider_invoice_id' => Arr::get($data, 'hoadon68_id') ?? Arr::get($data, 'id'),
            'provider_invoice_key' => Arr::get($data, 'key_api'),
            'invoice_number' => (string) (Arr::get($data, 'inv_invoiceNumber') ?? Arr::get($data, 'shdon') ?? ''),
            'invoice_series' => Arr::get($data, 'inv_invoiceSeries') ?? Arr::get($data, 'khieu') ?? $series['value'],
            'provider_status' => Arr::get($data, 'tthai') ?? Arr::get($data, 'trang_thai') ?? 'issued',
        ];
    }

    public function buildInvoicePayload(InvoiceRequest $invoiceRequest, string $invoiceSeries): array
    {
        $payment = $invoiceRequest->payment;
        if (!$payment) {
            throw new RuntimeException('Không tìm thấy payment để phát hành hóa đơn.');
        }

        $title = $payment->course?->title
            ?? $payment->ebook?->title
            ?? $payment->webinar?->title
            ?? 'Đơn hàng Sonet';

        $productId = $payment->course?->id
            ?? $payment->ebook?->id
            ?? $payment->webinar?->id
            ?? $payment->id;

        $originalAmount = (float) ($payment->original_amount ?? $payment->amount ?? 0);
        $discountAmount = (float) ($payment->discount_amount ?? 0);
        $netAmount = max($originalAmount - $discountAmount, 0);
        if ($netAmount <= 0) {
            $netAmount = (float) ($payment->amount ?? 0);
        }

        $vatRate = (float) config('minvoice.vat_rate', 0);
        $vatAmount = round($netAmount * ($vatRate / 100), 2);
        $totalAmount = round($netAmount + $vatAmount, 2);

        $buyerName = trim((string) ($payment->user?->name ?? ''));
        $displayName = $buyerName !== '' ? $buyerName : $invoiceRequest->company_name;
        $itemCodePrefix = trim((string) config('minvoice.item_code_prefix', 'SONET'));
        $itemCode = sprintf('%s-%s-%s', $itemCodePrefix, strtoupper((string) ($payment->product_type ?? 'item')), $productId);

        return [
            'editmode' => 1,
            'data' => [[
                'inv_invoiceSeries' => $invoiceSeries,
                'inv_invoiceIssuedDate' => now()->format('Y-m-d'),
                'inv_currencyCode' => config('minvoice.currency_code', 'VND'),
                'inv_exchangeRate' => (float) config('minvoice.exchange_rate', 1),
                'so_benh_an' => (string) ($payment->order_code ?? ('PAY-' . $payment->id)),
                'inv_paymentMethodName' => config('minvoice.payment_method_name', 'Chuyển khoản'),
                'inv_buyerDisplayName' => Str::limit($displayName, 100, ''),
                'inv_buyerLegalName' => Str::limit($invoiceRequest->company_name, 400, ''),
                'inv_buyerTaxCode' => Str::limit($invoiceRequest->tax_code, 14, ''),
                'inv_buyerAddressLine' => Str::limit($invoiceRequest->company_address, 400, ''),
                'inv_buyerEmail' => Str::limit($invoiceRequest->invoice_email, 50, ''),
                'buyerTel' => $payment->user?->phone,
                'inv_discountAmount' => round($discountAmount, 2),
                'inv_TotalAmountWithoutVat' => round($netAmount, 2),
                'inv_vatAmount' => $vatAmount,
                'inv_TotalAmount' => $totalAmount,
                'key_api' => $this->buildProviderKey($invoiceRequest),
                'details' => [[
                    'data' => [[
                        'tchat' => 1,
                        'stt_rec0' => 1,
                        'inv_itemCode' => Str::limit($itemCode, 50, ''),
                        'inv_itemName' => Str::limit($title, 500, ''),
                        'inv_unitCode' => config('minvoice.unit_code', 'Goi'),
                        'inv_quantity' => 1,
                        'inv_unitPrice' => round($originalAmount > 0 ? $originalAmount : $netAmount, 2),
                        'inv_discountPercentage' => 0,
                        'inv_discountAmount' => round($discountAmount, 2),
                        'inv_TotalAmountWithoutVat' => round($netAmount, 2),
                        'ma_thue' => (string) config('minvoice.vat_code', '0'),
                        'inv_vatAmount' => $vatAmount,
                        'inv_TotalAmount' => $totalAmount,
                    ]],
                ]],
            ]],
        ];
    }

    public function buildProviderKey(InvoiceRequest $invoiceRequest): string
    {
        return 'invoice-request-' . $invoiceRequest->id . '-payment-' . $invoiceRequest->payment_id;
    }

    private function resolveInvoiceSeries(): array
    {
        $configuredSeries = config('minvoice.default_invoice_series');
        $seriesList = Cache::remember(
            'minvoice.series.' . md5((string) config('minvoice.base_url') . '.' . (string) config('minvoice.tax_code')),
            now()->addHours(12),
            function () {
                $response = $this->request('get', '/api/Invoice68/GetTypeInvoiceSeries', [
                    'query' => [
                        'Type' => config('minvoice.invoice_type', 1),
                    ],
                ]);

                $body = $response->json();
                if (($body['ok'] ?? false) !== true || ($body['code'] ?? null) !== '00') {
                    throw new RuntimeException($body['message'] ?? 'Không lấy được danh sách ký hiệu hóa đơn từ Minvoice.');
                }

                return $body['data'] ?? [];
            }
        );

        if ($configuredSeries) {
            $matched = collect($seriesList)->first(function (array $series) use ($configuredSeries) {
                return ($series['value'] ?? null) === $configuredSeries
                    || ($series['khhdon'] ?? null) === $configuredSeries;
            });

            if ($matched) {
                return $matched;
            }
        }

        $first = collect($seriesList)->first();
        if (!$first || empty($first['value'])) {
            throw new RuntimeException('Minvoice chưa có ký hiệu hóa đơn hợp lệ để sử dụng.');
        }

        return $first;
    }

    private function getToken(): string
    {
        $cacheKey = 'minvoice.token.' . md5((string) config('minvoice.base_url') . '.' . (string) config('minvoice.username'));

        return Cache::remember($cacheKey, now()->addMinutes((int) config('minvoice.token_cache_minutes', 50)), function () {
            $response = Http::timeout((int) config('minvoice.request_timeout', 30))
                ->acceptJson()
                ->asJson()
                ->post($this->buildUrl('/api/Account/Login'), [
                    'username' => config('minvoice.username'),
                    'password' => config('minvoice.password'),
                    'ma_dvcs' => config('minvoice.branch_code', 'VP'),
                ]);

            $body = $response->json();
            if (!$response->successful() || ($body['ok'] ?? false) !== true || empty($body['token'])) {
                throw new RuntimeException($body['message'] ?? $body['error'] ?? 'Đăng nhập Minvoice thất bại.');
            }

            return (string) $body['token'];
        });
    }

    private function request(string $method, string $path, array $options = []): Response
    {
        $request = Http::timeout((int) config('minvoice.request_timeout', 30))
            ->acceptJson()
            ->withToken($this->getToken())
            ->withHeaders([
                'TaxCode' => (string) config('minvoice.tax_code'),
            ]);

        $response = $request->send(strtoupper($method), $this->buildUrl($path), $options);

        if ($response->status() === 401) {
            Cache::forget('minvoice.token.' . md5((string) config('minvoice.base_url') . '.' . (string) config('minvoice.username')));
            $response = $request->withToken($this->getToken())->send(strtoupper($method), $this->buildUrl($path), $options);
        }

        if (!$response->successful()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? ('Minvoice HTTP error: ' . $response->status());

            throw new RuntimeException($message);
        }

        return $response;
    }

    private function buildUrl(string $path): string
    {
        return config('minvoice.base_url') . '/' . ltrim($path, '/');
    }
}