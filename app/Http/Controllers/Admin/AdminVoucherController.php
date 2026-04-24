<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminVoucherController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $query = Voucher::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->with('courses:id,title')->paginate(20));
    }

    public function show($id)
    {
        $this->ensureAdmin();

        $voucher = Voucher::withCount('usages')->findOrFail($id);
        $voucher->load('courses:id,title');

        return response()->json(['voucher' => $voucher]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'nullable|in:active,inactive',
            'applicable_type' => 'nullable|in:all,specific',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        } else {
            $data['code'] = strtoupper(trim($data['code']));
        }

        $data['status'] = $data['status'] ?? 'active';
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        // Validate percent discount
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['discount_value' => ['Phần trăm giảm giá không thể vượt quá 100%']],
            ], 422);
        }

        $voucher = Voucher::create($data);

        // Sync course targeting
        if (($data['applicable_type'] ?? 'all') === 'specific' && !empty($request->course_ids)) {
            $voucher->courses()->sync($request->course_ids);
        }

        $voucher->load('courses:id,title');

        return response()->json([
            'message' => 'Tạo voucher thành công',
            'voucher' => $voucher,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $voucher = Voucher::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:vouchers,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'status' => 'nullable|in:active,inactive,expired',
            'applicable_type' => 'nullable|in:all,specific',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        // Validate percent discount
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['discount_value' => ['Phần trăm giảm giá không thể vượt quá 100%']],
            ], 422);
        }

        $voucher->update($data);

        // Sync course targeting
        if (($data['applicable_type'] ?? $voucher->applicable_type) === 'specific' && $request->has('course_ids')) {
            $voucher->courses()->sync($request->course_ids ?? []);
        } elseif (($data['applicable_type'] ?? null) === 'all') {
            $voucher->courses()->detach();
        }

        $voucher->load('courses:id,title');

        return response()->json([
            'message' => 'Cập nhật voucher thành công',
            'voucher' => $voucher,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $voucher = Voucher::findOrFail($id);
        
        // Check if voucher has been used
        if ($voucher->used_count > 0) {
            // Soft delete by marking as inactive
            $voucher->update(['status' => 'inactive']);
            return response()->json([
                'message' => 'Voucher đã được sử dụng nên chỉ có thể vô hiệu hóa',
            ]);
        }

        $voucher->delete();

        return response()->json([
            'message' => 'Xóa voucher thành công',
        ]);
    }

    public function stats()
    {
        $this->ensureAdmin();

        $stats = [
            'total' => Voucher::count(),
            'active' => Voucher::where('status', 'active')->count(),
            'inactive' => Voucher::where('status', 'inactive')->count(),
            'total_used' => Voucher::sum('used_count'),
        ];

        return response()->json($stats);
    }

    private function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }
}
