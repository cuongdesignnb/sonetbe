<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Carbon\Carbon;
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

        $this->normalizeCode($request);

        $validator = Validator::make($request->all(), [
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', 'unique:vouchers,code'],
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
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $this->normalizeValidityDates($validator->validated());
        
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
                'message' => 'Phần trăm giảm giá không thể vượt quá 100%.',
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

        $this->normalizeCode($request);

        $validator = Validator::make($request->all(), [
            'code' => ['nullable', 'string', 'max:50', 'unique:vouchers,code,' . $id],
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
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $this->normalizeValidityDates($validator->validated());

        if (!empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }

        // Validate percent discount
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return response()->json([
                'message' => 'Phần trăm giảm giá không thể vượt quá 100%.',
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

    /**
     * The admin panel sends valid_from / valid_until as ISO-8601 instants
     * (with offset). Convert them to the app timezone before storing, since
     * the datetime cast stores the wall-clock value as-is and validity is
     * compared against now() in the app timezone. Without this the voucher
     * window shifts by the difference between the admin's timezone and the
     * server's (7 hours for Vietnam vs UTC).
     */
    private function normalizeValidityDates(array $data): array
    {
        foreach (['valid_from', 'valid_until'] as $field) {
            if (!empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field])
                    ->setTimezone(config('app.timezone'));
            }
        }

        return $data;
    }

    private const VALIDATION_MESSAGES = [
        'required' => ':attribute là bắt buộc.',
        'unique' => ':attribute ":input" đã tồn tại (kể cả voucher đã vô hiệu). Vui lòng dùng mã khác.',
        'max' => ':attribute tối đa :max ký tự.',
        'min' => ':attribute phải từ :min trở lên.',
        'numeric' => ':attribute phải là số.',
        'integer' => ':attribute phải là số nguyên.',
        'date' => ':attribute không đúng định dạng ngày giờ.',
        'after_or_equal' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        'in' => ':attribute không hợp lệ.',
        'exists' => ':attribute không tồn tại.',
        'regex' => ':attribute chỉ được gồm chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.',
    ];

    private const VALIDATION_ATTRIBUTES = [
        'code' => 'Mã voucher',
        'name' => 'Tên voucher',
        'discount_type' => 'Loại giảm giá',
        'discount_value' => 'Giá trị giảm',
        'min_order_amount' => 'Đơn tối thiểu',
        'max_discount' => 'Giảm tối đa',
        'usage_limit' => 'Giới hạn sử dụng',
        'usage_per_user' => 'Lượt/người dùng',
        'valid_from' => 'Thời gian bắt đầu',
        'valid_until' => 'Thời gian kết thúc',
        'status' => 'Trạng thái',
        'applicable_type' => 'Phạm vi áp dụng',
        'course_ids' => 'Khóa học áp dụng',
        'course_ids.*' => 'Khóa học áp dụng',
    ];

    /**
     * Codes are stored upper-case. Normalise before validating so the unique
     * check compares the same value that will be saved (" mmaf " vs "MMAF").
     */
    private function normalizeCode(Request $request): void
    {
        $code = $request->input('code');
        if (is_string($code)) {
            $request->merge(['code' => strtoupper(trim($code)) ?: null]);
        }
    }

    /** 422 whose message lists the actual problems instead of "Validation failed". */
    private function validationError($validator)
    {
        return response()->json([
            'message' => implode(' ', $validator->errors()->all()),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }
}
