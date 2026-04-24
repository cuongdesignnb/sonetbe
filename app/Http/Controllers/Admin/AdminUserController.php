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
            ->with(['course' => function ($q) {
                $q->withTrashed()->select('id', 'title', 'thumbnail', 'price');
            }])
            ->where('status', 'active')
            ->get();

        $payments = CoursePayment::where('user_id', $id)
            ->where('status', 'paid')
            ->with(['course' => function ($q) {
                $q->withTrashed()->select('id', 'title');
            }])
            ->orderByDesc('paid_at')
            ->get(['id', 'course_id', 'amount', 'order_code', 'paid_at', 'product_type']);

        $totalRevenue = $payments->sum('amount');

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
                $q->where('status', 'active')->with(['course' => function ($cq) {
                    $cq->withTrashed()->select('id', 'title');
                }]);
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
            $courseNames = $user->enrollments->map(fn($e) => $e->course?->title ?? '')->filter()->implode(' | ');
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
