<?php

namespace App\Jobs;

use App\Models\InvoiceRequest;
use App\Services\MinvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $invoiceRequestId)
    {
    }

    public function handle(MinvoiceService $minvoiceService): void
    {
        $invoiceRequest = InvoiceRequest::with(['payment.user', 'payment.course', 'payment.ebook', 'payment.webinar'])
            ->find($this->invoiceRequestId);

        if (!$invoiceRequest) {
            return;
        }

        if ($invoiceRequest->status === 'completed') {
            return;
        }

        if (!$invoiceRequest->payment || $invoiceRequest->payment->status !== 'paid') {
            return;
        }

        if (!$minvoiceService->isEnabled()) {
            $invoiceRequest->update([
                'status' => 'rejected',
                'provider' => 'minvoice',
                'last_error' => 'Minvoice chưa được cấu hình đầy đủ.',
                'retry_count' => $invoiceRequest->retry_count + 1,
                'processing_completed_at' => now(),
            ]);

            return;
        }

        $invoiceRequest->update([
            'status' => 'processing',
            'provider' => 'minvoice',
            'processing_started_at' => now(),
            'last_error' => null,
        ]);

        try {
            $result = $minvoiceService->issueInvoice($invoiceRequest);

            $invoiceRequest->update([
                'status' => 'completed',
                'provider' => 'minvoice',
                'provider_invoice_id' => $result['provider_invoice_id'] ?? null,
                'provider_invoice_key' => $result['provider_invoice_key'] ?? null,
                'invoice_number' => $result['invoice_number'] ?? null,
                'invoice_series' => $result['invoice_series'] ?? null,
                'provider_status' => (string) ($result['provider_status'] ?? 'issued'),
                'provider_request_payload' => $result['request_payload'] ?? null,
                'provider_response_payload' => $result['response_payload'] ?? null,
                'processing_completed_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Minvoice issue failed', [
                'invoice_request_id' => $invoiceRequest->id,
                'payment_id' => $invoiceRequest->payment_id,
                'error' => $exception->getMessage(),
            ]);

            $invoiceRequest->update([
                'status' => 'rejected',
                'provider' => 'minvoice',
                'retry_count' => $invoiceRequest->retry_count + 1,
                'last_error' => $exception->getMessage(),
                'processing_completed_at' => now(),
            ]);
        }
    }
}