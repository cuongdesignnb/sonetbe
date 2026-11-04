<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoursePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
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

        $query = User::query()
            ->withCount(['enrollments as enrollments_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->addSelect([
                'total_revenue' => CoursePayment::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', 'paid'),
            ]);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('course_id')) {
            $courseId = (int) $request->input('course_id');
            $query->whereHas('enrollments', function ($q) use ($courseId) {
                $q->where('course_id', $courseId)->where('status', 'active');
            });
        }

        $items = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $items]);
    }

    public function show($id)
    {
        $this->ensureAdmin();

        $user = User::findOrFail($id);

        $enrollments = $user->enrollments()
            ->with([
                'course' => function ($q) {
                    $q->withTrashed()->select('id', 'title', 'thumbnail', 'price');
                },
                'section:id,title,price',
                'durationTier:id,label,duration_days,price',
                'latestPayment.voucher:id,code',
            ])
            ->withCount(['payments as paid_orders_count' => function ($q) {
                $q->where('status', 'paid');
            }])
            ->whereIn('status', ['active', 'pending'])
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(function ($e) use ($user) {
                return [
                    'id' => $e->id,
                    'status' => $e->status,
                    'course' => $e->course ? ['id' => $e->course->id, 'title' => $e->course->title] : null,
                    'purchase_type' => $e->section_id ? 'chapter' : 'full',
                    'purchase_label' => $e->purchaseLabel(),
                    'section' => $e->section ? ['id' => $e->section->id, 'title' => $e->section->title, 'price' => $e->section->price] : null,
                    'duration_tier' => $e->durationTier ? [
                        'label' => $e->durationTier->label,
                        'duration_days' => $e->durationTier->duration_days,
                        'price' => $e->durationTier->price,
                    ] : null,
                    'enrolled_at' => $e->enrolled_at,
                    'expires_at' => $e->expires_at,
                    'days_remaining' => $e->daysRemaining(),
                    'access_state' => $e->accessState(),
                    'completed_at' => $e->completed_at,
                    'progress' => $e->course ? $user->getCourseProgress($e->course->id) : 0,
                    'voucher_code' => $e->latestPayment?->voucher?->code,
                    'paid_orders_count' => (int) ($e->paid_orders_count ?? 0),
                ];
            });

        $payments = CoursePayment::where('user_id', $id)
            ->where('status', 'paid')
            ->with([
                'course' => function ($q) {
                    $q->withTrashed()->select('id', 'title');
                },
                'section:id,title',
                'durationTier:id,label,duration_days',
                'voucher:id,code',
            ])
            ->orderByDesc('paid_at')
            ->get()
            ->map(function ($p) {
                if ($p->product_type === 'chapter' || $p->section_id) {
                    $label = 'Chương lẻ' . ($p->section ? ': ' . $p->section->title : '');
                } elseif (in_array($p->product_type, ['ebook', 'webinar'], true)) {
                    $label = $p->product_type === 'ebook' ? 'Ebook' : 'Webinar';
                } else {
                    $label = 'Toàn khoá' . ($p->durationTier ? ' · ' . $p->durationTier->label : '');
                }

                $code = (string) $p->order_code;
                $source = match (true) {
                    str_starts_with($code, 'MANUAL-') => 'manual',
                    str_starts_with($code, 'BULK-') => 'bulk',
                    str_starts_with($code, 'FREE-') => $p->voucher_id ? 'voucher' : 'free',
                    default => 'sepay',
                };

                return [
                    'id' => $p->id,
                    'order_code' => $p->order_code,
                    'product_type' => $p->product_type,
                    'product_label' => $label,
                    'course' => $p->course ? ['id' => $p->course->id, 'title' => $p->course->title] : null,
                    'original_amount' => $p->original_amount,
                    'discount_amount' => $p->discount_amount,
                    'amount' => $p->amount,
                    'voucher_code' => $p->voucher?->code,
                    'source' => $source,
                    'paid_at' => $p->paid_at,
                ];
            });

        $totalRevenue = $payments->sum(fn ($p) => (float) $p['amount']);

        return response()->json([
            'user' => $user,
            'enrollments' => $enrollments,
            'payments' => $payments,
            'total_revenue' => $totalRevenue,
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureAdmin();

        $query = User::query()
            ->with(['enrollments' => function ($q) {
                $q->where('status', 'active')->with([
                    'course' => function ($cq) {
                        $cq->withTrashed()->select('id', 'title');
                    },
                    'section:id,title',
                    'durationTier:id,label',
                ]);
            }]);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->filled('course_id')) {
            $courseId = (int) $request->input('course_id');
            $query->whereHas('enrollments', function ($q) use ($courseId) {
                $q->where('course_id', $courseId)->where('status', 'active');
            });
        }

        // If specific user IDs are provided (bulk selection)
        if ($request->filled('ids')) {
            $ids = array_map('intval', explode(',', $request->input('ids')));
            $query->whereIn('id', $ids);
        }

        $users = $query->orderByDesc('created_at')->get();

        // Get revenue per user
        $userIds = $users->pluck('id')->toArray();
        $revenues = CoursePayment::whereIn('user_id', $userIds)
            ->where('status', 'paid')
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        // Generate CSV (Excel-compatible with BOM for UTF-8)
        $bom = "\xEF\xBB\xBF";
        $csv = $bom . "ID,Họ tên,Email,Điện thoại,Vai trò,Trạng thái,Khóa học đã đăng ký,Doanh thu (VNĐ),Ngày tạo\n";

        foreach ($users as $user) {
            // e.g. "AI FOR AFFILIATE (Toàn khoá · 3 tháng, hạn 20/01/2027)"
            $courseNames = $user->enrollments->map(function ($e) {
                if (!$e->course) {
                    return '';
                }
                $label = $e->purchaseLabel();
                if ($e->expires_at) {
                    $label .= ', ' . ($e->isExpired() ? 'hết hạn ' : 'hạn ') . $e->expires_at->format('d/m/Y');
                } elseif (!str_ends_with($label, 'Vĩnh viễn')) {
                    $label .= ', vĩnh viễn';
                }
                return $e->course->title . ' (' . $label . ')';
            })->filter()->implode(' | ');
            $revenue = $revenues[$user->id] ?? 0;
            $status = $user->is_active ? 'Hoạt động' : 'Khóa';
            $roleMap = ['admin' => 'Admin', 'instructor' => 'Giảng viên', 'student' => 'Học viên'];
            $role = $roleMap[$user->role] ?? $user->role;

            $csv .= implode(',', [
                $user->id,
                '"' . str_replace('"', '""', $user->name ?? '') . '"',
                '"' . str_replace('"', '""', $user->email ?? '') . '"',
                '"' . str_replace('"', '""', $user->phone ?? '') . '"',
                $role,
                $status,
                '"' . str_replace('"', '""', $courseNames) . '"',
                number_format($revenue, 0, '.', ''),
                $user->created_at?->format('Y-m-d H:i') ?? '',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="users_' . date('Ymd_His') . '.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,instructor,student',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,instructor,student',
            'is_active' => 'nullable|boolean',
            'avatar' => 'nullable|string|max:2048',
            'bio' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:30',
            'date_of_birth' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
