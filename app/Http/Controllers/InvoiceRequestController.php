<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInvoiceRequestJob;
use App\Models\CoursePayment;
use App\Models\InvoiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceRequestController extends Controller
{
    /**
     * Store a new invoice request for a payment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:course_payments,id',
            'company_name' => 'required|string|max:255',
            'tax_code' => 'required|string|max:20',
            'company_address' => 'required|string|max:500',
            'invoice_email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user owns this payment
        $user = $request->user();
        $payment = CoursePayment::query()
            ->where('id', $request->payment_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Check if invoice request already exists for this payment
        $existing = InvoiceRequest::where('payment_id', $request->payment_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && in_array($existing->status, ['processing', 'completed'], true)) {
            return response()->json([
                'message' => 'Yêu cầu hóa đơn đang được xử lý hoặc đã phát hành, không thể chỉnh sửa.',
                'invoice_request' => $existing,
            ], 409);
        }

        $payload = [
            'company_name' => $request->company_name,
            'tax_code' => $request->tax_code,
            'company_address' => $request->company_address,
            'invoice_email' => $request->invoice_email,
            'status' => 'pending',
            'provider' => null,
            'provider_invoice_id' => null,
            'provider_invoice_key' => null,
            'invoice_number' => null,
            'invoice_series' => null,
            'provider_status' => null,
            'provider_request_payload' => null,
            'provider_response_payload' => null,
            'last_error' => null,
            'processing_started_at' => null,
            'processing_completed_at' => null,
        ];

        if ($existing) {
            $existing->update($payload);

            if ($payment->status === 'paid') {
                ProcessInvoiceRequestJob::dispatch($existing->id);
            }

            return response()->json([
                'message' => 'Yêu cầu xuất hóa đơn đã được cập nhật',
                'invoice_request' => $existing->fresh('payment'),
            ]);
        }

        $invoiceRequest = InvoiceRequest::create([
            'user_id' => $user->id,
            'payment_id' => $request->payment_id,
            ...$payload,
        ]);

        if ($payment->status === 'paid') {
            ProcessInvoiceRequestJob::dispatch($invoiceRequest->id);
        }

        return response()->json([
            'message' => $payment->status === 'paid'
                ? 'Yêu cầu xuất hóa đơn đã được lưu và đang phát hành.'
                : 'Yêu cầu xuất hóa đơn đã được lưu. Hóa đơn sẽ được phát hành sau khi thanh toán thành công.',
            'invoice_request' => $invoiceRequest->load('payment'),
        ], 201);
    }

    public function showByPayment(Request $request, int $paymentId)
    {
        $invoiceRequest = InvoiceRequest::with('payment')
            ->where('payment_id', $paymentId)
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'invoice_request' => $invoiceRequest,
        ]);
    }
}
