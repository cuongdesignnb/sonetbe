<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Validate a voucher code for a specific course
     */
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'course_id' => 'required|exists:courses,id',
            'section_id' => 'nullable|exists:course_sections,id',
        ]);

        $user = Auth::user();
        $code = strtoupper(trim($request->code));
        $course = Course::findOrFail($request->course_id);
        
        $section = null;
        if ($request->section_id) {
            $section = CourseSection::where('course_id', $course->id)->findOrFail($request->section_id);
        }

        $voucher = Voucher::findByCode($code);

        if (!$voucher) {
            return response()->json([
                'valid' => false,
                'message' => 'Mã giảm giá không tồn tại',
            ], 404);
        }

        $orderAmount = $section ? (float) $section->price : (float) $course->price;
        $error = $voucher->getValidationError($orderAmount, $user->id, $course->id);

        if ($error) {
            return response()->json([
                'valid' => false,
                'message' => $error,
            ], 400);
        }

        $discount = $voucher->calculateDiscount($orderAmount);
        $finalAmount = max(0, $orderAmount - $discount);

        return response()->json([
            'valid' => true,
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_type' => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
            ],
            'original_amount' => $orderAmount,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
            'message' => 'Áp dụng mã giảm giá thành công',
        ]);
    }

    /**
     * Get voucher info by code (public)
     */
    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));
        $voucher = Voucher::findByCode($code);

        if (!$voucher || !$voucher->is_valid) {
            return response()->json([
                'valid' => false,
                'message' => $voucher ? 'Mã giảm giá không hợp lệ hoặc đã hết hạn' : 'Mã giảm giá không tồn tại',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'voucher' => [
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_type' => $voucher->discount_type,
                'discount_value' => $voucher->discount_value,
                'min_order_amount' => $voucher->min_order_amount,
                'max_discount' => $voucher->max_discount,
            ],
        ]);
    }
}
