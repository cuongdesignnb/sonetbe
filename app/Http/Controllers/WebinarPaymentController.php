<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use App\Mail\WebinarRegistrationMail;
use App\Services\MailConfigService;
use App\Services\SePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WebinarPaymentController extends Controller
{
    /**
     * Checkout for a paid webinar – creates a CoursePayment + returns QR/bank info.
     */
    public function checkout(Request $request, string $slug)
    {
        $webinar = Webinar::public()->where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Free webinars don't need payment
        if ($webinar->is_free || (float) $webinar->price <= 0) {
            return response()->json(['message' => 'Webinar này miễn phí, không cần thanh toán.'], 400);
        }

        if (!in_array($webinar->status, ['upcoming', 'live', 'completed'])) {
            return response()->json(['message' => 'Webinar không khả dụng.'], 422);
        }

        // Check if already paid
        $existingPaid = CoursePayment::where('user_id', $user->id)
            ->where('webinar_id', $webinar->id)
            ->where('product_type', 'webinar')
            ->where('status', 'paid')
            ->first();

        if ($existingPaid) {
            return response()->json([
                'message' => 'Bạn đã thanh toán webinar này rồi.',
                'paid' => true,
                'payment' => ['id' => $existingPaid->id, 'status' => 'paid'],
            ]);
        }

        $price = (float) $webinar->price;

        // Find or create pending payment
        $payment = CoursePayment::where('user_id', $user->id)
            ->where('webinar_id', $webinar->id)
            ->where('product_type', 'webinar')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (!$payment) {
            $payment = CoursePayment::create([
                'product_type' => 'webinar',
                'user_id' => $user->id,
                'webinar_id' => $webinar->id,
                'original_amount' => $price,
                'discount_amount' => 0,
                'amount' => $price,
                'status' => 'pending',
                'order_code' => 'TEMP-' . uniqid(),
            ]);
        }

        $orderCode = SePayService::ensureOrderCode($payment);
        $qrUrl = SePayService::buildQrUrl($payment);
        $bank = SePayService::getBankInfo();

        return response()->json([
            'message' => 'Payment required',
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $price,
                'order_code' => $orderCode,
                'transfer_content' => $orderCode,
                'course' => [
                    'id' => $webinar->id,
                    'title' => $webinar->title,
                    'description' => $webinar->description,
                    'thumbnail' => $webinar->thumbnail,
                    'price' => $webinar->price,
                ],
            ],
            'qr_url' => $qrUrl,
            'bank' => $bank,
        ]);
    }

    /**
     * Check payment status for a webinar payment.
     */
    public function status($paymentId)
    {
        $payment = CoursePayment::with(['webinar', 'invoiceRequest'])->findOrFail($paymentId);
        $user = Auth::user();

        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bank = SePayService::getBankInfo();
        $qrUrl = SePayService::buildQrUrl($payment);

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'order_code' => $payment->order_code,
                'transfer_content' => $payment->order_code,
                'paid_at' => $payment->paid_at,
                'qr_url' => $qrUrl,
                'bank_code' => !empty($bank['name']) ? $bank['name'] : ($bank['code'] ?? null),
                'bank_account_number' => $bank['account_number'] ?? null,
                'bank_account_name' => $bank['account_name'] ?? null,
                'course' => $payment->webinar ? [
                    'id' => $payment->webinar->id,
                    'title' => $payment->webinar->title,
                    'description' => $payment->webinar->description,
                    'thumbnail' => $payment->webinar->thumbnail,
                    'price' => $payment->webinar->price,
                ] : null,
            ],
            'invoice_request' => $payment->invoiceRequest,
        ]);
    }
}
