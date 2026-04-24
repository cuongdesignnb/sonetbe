<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\SePayService;

class PaymentController extends Controller
{
    public function checkout(Request $request, $courseId)
    {
        $course = Course::findByIdOrSlugOrFail($courseId);
        $user = Auth::user();

        if ($user->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'Already enrolled'], 400);
        }

        $originalPrice = (float) $course->price;
        $voucherCode = $request->input('voucher_code');
        $voucher = null;
        $discountAmount = 0;
        $finalAmount = $originalPrice;

        // Validate and apply voucher if provided
        if ($voucherCode) {
            $voucher = Voucher::findByCode($voucherCode);
            
            if (!$voucher) {
                return response()->json(['message' => 'Mã giảm giá không tồn tại'], 400);
            }

            $voucherError = $voucher->getValidationError($originalPrice, $user->id);
            if ($voucherError) {
                return response()->json(['message' => $voucherError], 400);
            }

            $discountAmount = $voucher->calculateDiscount($originalPrice);
            $finalAmount = max(0, $originalPrice - $discountAmount);
        }

        // If course is free or fully discounted
        if ($finalAmount <= 0) {
            try {
                return DB::transaction(function () use ($user, $course, $voucher, $discountAmount, $originalPrice) {
                    // Check if already enrolled
                    $existingEnrollment = Enrollment::where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->where('status', 'active')
                        ->first();
                    
                    if ($existingEnrollment) {
                        return response()->json([
                            'message' => 'Enrolled successfully',
                            'enrollment' => $existingEnrollment,
                            'discount_applied' => false,
                        ]);
                    }

                    $enrollment = Enrollment::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'course_id' => $course->id,
                        ],
                        [
                            'status' => 'active',
                            'enrolled_at' => now(),
                            'amount_paid' => 0,
                        ]
                    );

                    // Record voucher usage if used
                    if ($voucher && $discountAmount > 0) {
                        VoucherUsage::create([
                            'voucher_id' => $voucher->id,
                            'user_id' => $user->id,
                            'course_payment_id' => null,
                            'discount_amount' => $discountAmount,
                        ]);
                        $voucher->incrementUsage();
                    }

                    return response()->json([
                        'message' => 'Enrolled successfully',
                        'enrollment' => $enrollment,
                        'discount_applied' => $discountAmount > 0,
                        'discount_amount' => $discountAmount,
                    ]);
                });
            } catch (\Exception $e) {
                \Log::error('Free enrollment failed: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'voucher_code' => $voucherCode,
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'message' => 'Không thể đăng ký khóa học. Vui lòng thử lại.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        }

        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'status' => 'pending',
                'amount_paid' => $finalAmount,
            ]
        );

        if ($enrollment->status === 'active') {
            return response()->json(['message' => 'Already enrolled'], 400);
        }

        // Update enrollment amount if voucher changes price
        if ($enrollment->amount_paid != $finalAmount) {
            $enrollment->update(['amount_paid' => $finalAmount]);
        }

        $payment = CoursePayment::where('enrollment_id', $enrollment->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (!$payment) {
            $payment = CoursePayment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'voucher_id' => $voucher?->id,
                'original_amount' => $originalPrice,
                'discount_amount' => $discountAmount,
                'amount' => $finalAmount,
                'status' => 'pending',
                'order_code' => 'TEMP-' . uniqid(),
            ]);
        } else {
            // Update existing payment with voucher info
            $payment->update([
                'voucher_id' => $voucher?->id,
                'original_amount' => $originalPrice,
                'discount_amount' => $discountAmount,
                'amount' => $finalAmount,
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
                'original_amount' => $originalPrice,
                'discount_amount' => $discountAmount,
                'amount' => $finalAmount,
                'order_code' => $orderCode,
                'transfer_content' => $orderCode,
                'voucher_applied' => $voucher ? [
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                ] : null,
            ],
            'enrollment_id' => $enrollment->id,
            'qr_url' => $qrUrl,
            'bank' => $bank,
        ]);
    }

    public function status($paymentId)
    {
        $payment = CoursePayment::with(['enrollment', 'voucher', 'course', 'invoiceRequest'])->findOrFail($paymentId);
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
                'original_amount' => $payment->original_amount,
                'discount_amount' => $payment->discount_amount,
                'amount' => $payment->amount,
                'order_code' => $payment->order_code,
                'transfer_content' => $payment->order_code,
                'paid_at' => $payment->paid_at,
                'qr_url' => $qrUrl,
                'bank_code' => !empty($bank['name']) ? $bank['name'] : ($bank['code'] ?? null),
                'bank_account_number' => $bank['account_number'] ?? null,
                'bank_account_name' => $bank['account_name'] ?? null,
                'voucher' => $payment->voucher ? [
                    'code' => $payment->voucher->code,
                    'name' => $payment->voucher->name,
                ] : null,
                'course' => $payment->course ? [
                    'id' => $payment->course->id,
                    'title' => $payment->course->title,
                    'description' => $payment->course->short_description ?? $payment->course->description ?? null,
                    'thumbnail' => $payment->course->thumbnail,
                    'price' => $payment->course->price,
                ] : null,
            ],
            'invoice_request' => $payment->invoiceRequest,
            'enrollment_status' => $payment->enrollment?->status,
        ]);
    }
}
