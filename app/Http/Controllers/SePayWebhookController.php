<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInvoiceRequestJob;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\InvoiceRequest;
use App\Models\VoucherUsage;
use App\Models\WebinarRegistration;
use App\Mail\WebinarRegistrationMail;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\SePayService;

class SePayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('SePay webhook received', [
            'id' => $request->integer('id'),
            'transferType' => $request->string('transferType')->value(),
            'transferAmount' => $request->integer('transferAmount'),
            'referenceCode' => $request->string('referenceCode')->value(),
            'code' => $request->string('code')->value(),
            'content' => $request->string('content')->value(),
            'accountNumber' => $request->string('accountNumber')->value(),
        ]);

        $token = $this->extractApiKey($request);
        $expected = SePayService::getWebhookToken();

        if ($expected && $token !== $expected) {
            return response()->json(['message' => 'Invalid Token'], 401);
        }

        $transferType = strtolower($request->string('transferType')->value() ?? '');
        if ($transferType !== 'in') {
            return response()->json(['success' => true]);
        }

        $sepayId = $request->integer('id');
        $transferAmount = (float) ($request->integer('transferAmount') ?? 0);
        $content = $request->string('content')->value() ?? '';
        $code = $request->string('code')->value() ?? '';

        $paymentId = $this->extractPaymentId($code, $content);
        if (!$paymentId) {
            Log::warning('SePay webhook missing payment id', [
                'content' => $content,
                'code' => $code,
                'sepay_id' => $sepayId,
            ]);

            $this->logTransaction($request);

            return response()->json(['success' => true]);
        }

        $payment = CoursePayment::query()->whereKey($paymentId)->first();
        if (!$payment || $payment->status === 'paid') {
            $this->logTransaction($request);

            return response()->json(['success' => true]);
        }

        if ($sepayId && CoursePayment::where('sepay_txn_id', $sepayId)->exists()) {
            $this->logTransaction($request);

            return response()->json(['success' => true]);
        }

        if ($transferAmount < (float) $payment->amount) {
            Log::warning('SePay payment amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => (float) $payment->amount,
                'received' => $transferAmount,
                'sepay_id' => $sepayId,
            ]);

            $this->logTransaction($request);

            return response()->json(['success' => true]);
        }

        $payment->update([
            'status' => 'paid',
            'sepay_txn_id' => $sepayId,
            'transfer_amount' => $transferAmount,
            'raw_payload' => (array) $request->all(),
            'paid_at' => now(),
        ]);

        $invoiceRequest = InvoiceRequest::query()
            ->where('payment_id', $payment->id)
            ->whereIn('status', ['pending', 'rejected'])
            ->latest('id')
            ->first();

        if ($invoiceRequest) {
            ProcessInvoiceRequestJob::dispatch($invoiceRequest->id);
        }

        // Record voucher usage if voucher was applied
        if ($payment->voucher_id && $payment->discount_amount > 0) {
            VoucherUsage::firstOrCreate(
                [
                    'voucher_id' => $payment->voucher_id,
                    'user_id' => $payment->user_id,
                    'course_payment_id' => $payment->id,
                ],
                [
                    'discount_amount' => $payment->discount_amount,
                ]
            );
            
            // Increment voucher usage count
            $payment->voucher?->incrementUsage();
        }

        // Handle based on product type
        if ($payment->product_type === 'ebook' && $payment->ebook_id) {
            $payment->ebook?->increment('download_count');
        } elseif ($payment->product_type === 'webinar' && $payment->webinar_id) {
            // Auto-register user for the webinar after payment
            $webinar = $payment->webinar;
            if ($webinar) {
                WebinarRegistration::firstOrCreate(
                    [
                        'webinar_id' => $webinar->id,
                        'user_id' => $payment->user_id,
                    ],
                    [
                        'status' => 'registered',
                    ]
                );

                // Send registration email with zoom link
                try {
                    $user = $payment->user;
                    if ($user) {
                        MailConfigService::applyFromSettings();
                        Mail::to($user->email)->send(new WebinarRegistrationMail($webinar, $user));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send webinar registration email after payment: ' . $e->getMessage());
                }
            }
        } else {
            $enrollment = Enrollment::query()->whereKey($payment->enrollment_id)->first();
            if ($enrollment) {
                $enrollment->update([
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'amount_paid' => $payment->amount,
                    'payment_id' => (string) $sepayId,
                ]);
            }
        }

        $this->logTransaction($request);

        return response()->json(['success' => true]);
    }

    private function extractApiKey(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        $position = strrpos($header, 'Apikey ');

        if ($position !== false) {
            $header = substr($header, $position + 7);

            return str_contains($header, ',') ? (strstr($header, ',', true) ?: null) : $header;
        }

        return null;
    }

    private function extractPaymentId(string $code, string $content): ?int
    {
        $pattern = preg_quote(SePayService::getPattern(), '/');

        if ($code) {
            if (preg_match('/^' . $pattern . '(\d+)/', $code, $matches)) {
                return isset($matches[1]) ? (int) $matches[1] : null;
            }
        }

        if ($content && preg_match('/\b' . $pattern . '(\d+)/', $content, $matches)) {
            return isset($matches[1]) ? (int) $matches[1] : null;
        }

        return null;
    }

    private function logTransaction(Request $request): void
    {
        if (!Schema::hasTable('sepay_transactions')) {
            return;
        }

        try {
            DB::table('sepay_transactions')->insert([
                'sepay_id' => $request->integer('id') ?: null,
                'gateway' => $request->string('gateway')->value() ?? '',
                'transactionDate' => $request->string('transactionDate')->value() ?? '',
                'accountNumber' => $request->string('accountNumber')->value() ?? '',
                'subAccount' => $request->string('subAccount')->value(),
                'code' => $request->string('code')->value(),
                'content' => $request->string('content')->value() ?? '',
                'transferType' => $request->string('transferType')->value() ?? '',
                'description' => $request->string('description')->value(),
                'transferAmount' => $request->integer('transferAmount') ?? 0,
                'accumulated' => $request->integer('accumulated') ?: null,
                'referenceCode' => $request->string('referenceCode')->value(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log SePay transaction', ['error' => $e->getMessage()]);
        }
    }
}
