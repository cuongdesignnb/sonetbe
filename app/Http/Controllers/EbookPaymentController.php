<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SePayService;

class EbookPaymentController extends Controller
{
    public function checkout(Request $request, $ebookId)
    {
        $ebook = Ebook::findOrFail($ebookId);
        $user = Auth::user();

        // Check if already purchased
        $existing = CoursePayment::where('user_id', $user->id)
            ->where('ebook_id', $ebook->id)
            ->where('product_type', 'ebook')
            ->where('status', 'paid')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Bạn đã mua ebook này rồi',
                'purchased' => true,
                'payment' => ['id' => $existing->id, 'status' => 'paid'],
                'file_url' => $ebook->file_url,
            ]);
        }

        $price = (float) $ebook->price;

        // If free
        if ($price <= 0) {
            $payment = CoursePayment::create([
                'product_type' => 'ebook',
                'user_id' => $user->id,
                'ebook_id' => $ebook->id,
                'original_amount' => 0,
                'discount_amount' => 0,
                'amount' => 0,
                'status' => 'paid',
                'order_code' => 'FREE-EB' . $ebook->id . '-' . $user->id . '-' . time(),
                'paid_at' => now(),
            ]);

            $ebook->increment('download_count');

            return response()->json([
                'message' => 'Đã nhận ebook miễn phí!',
                'purchased' => true,
                'payment' => ['id' => $payment->id, 'status' => 'paid'],
                'file_url' => $ebook->file_url,
            ]);
        }

        // Find or create pending payment
        $payment = CoursePayment::where('user_id', $user->id)
            ->where('ebook_id', $ebook->id)
            ->where('product_type', 'ebook')
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (!$payment) {
            $payment = CoursePayment::create([
                'product_type' => 'ebook',
                'user_id' => $user->id,
                'ebook_id' => $ebook->id,
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
            ],
            'qr_url' => $qrUrl,
            'bank' => $bank,
        ]);
    }

    public function status($paymentId)
    {
        $payment = CoursePayment::with(['ebook', 'invoiceRequest'])->findOrFail($paymentId);
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
                'file_url' => $payment->status === 'paid' && $payment->ebook
                    ? $payment->ebook->file_url
                    : null,
                'course' => $payment->ebook ? [
                    'id' => $payment->ebook->id,
                    'title' => $payment->ebook->title,
                    'description' => $payment->ebook->description,
                    'thumbnail' => $payment->ebook->thumbnail,
                    'price' => $payment->ebook->price,
                    'slug' => $payment->ebook->slug,
                ] : null,
            ],
            'invoice_request' => $payment->invoiceRequest,
        ]);
    }
}
