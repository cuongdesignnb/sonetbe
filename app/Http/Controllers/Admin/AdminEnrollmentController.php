<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminEnrollmentController extends Controller
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

        $query = Enrollment::query()->with(['user', 'course', 'latestPayment']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('payment_status')) {
            $paymentStatus = $request->string('payment_status')->toString();
            if ($paymentStatus === 'free') {
                $query->where(function ($q) {
                    $q->where('amount_paid', 0)->orWhereNull('amount_paid');
                })->whereDoesntHave('latestPayment');
            } else {
                $query->whereHas('latestPayment', function ($q) use ($paymentStatus) {
                    $q->where('status', $paymentStatus);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('created_at')->get();

        $data = $items->map(function ($enrollment) {
            $payment = $enrollment->latestPayment;
            $paymentStatus = $payment?->status;
            if (!$payment && (float) ($enrollment->amount_paid ?? 0) <= 0) {
                $paymentStatus = 'free';
            }

            return [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at,
                'completed_at' => $enrollment->completed_at,
                'amount_paid' => $enrollment->amount_paid,
                'created_at' => $enrollment->created_at,
                'user' => $enrollment->user ? [
                    'id' => $enrollment->user->id,
                    'name' => $enrollment->user->name,
                    'email' => $enrollment->user->email,
                ] : null,
                'course' => $enrollment->course ? [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'price' => $enrollment->course->price,
                ] : null,
                'payment_status' => $paymentStatus,
                'payment' => $payment ? [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'order_code' => $payment->order_code,
                    'paid_at' => $payment->paid_at,
                    'sepay_txn_id' => $payment->sepay_txn_id,
                    'transfer_amount' => $payment->transfer_amount,
                ] : null,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Manually enroll a user in a course (admin only).
     * Creates enrollment + optional payment record for revenue tracking.
     * Supports duration tier selection with expiry calculation.
     */
    public function manualEnroll(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'duration_tier_id' => 'nullable|integer|exists:course_duration_tiers,id',
            'amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->integer('user_id');
        $courseId = $request->integer('course_id');
        $tierId = $request->input('duration_tier_id');
        $amount = (float) ($request->input('amount', 0));
        $note = $request->input('note', '');

        // Validate tier belongs to course if provided
        $tier = null;
        $expiresAt = null;
        if ($tierId) {
            $tier = \App\Models\CourseDurationTier::where('id', $tierId)
                ->where('course_id', $courseId)
                ->where('is_active', true)
                ->first();

            if (!$tier) {
                return response()->json([
                    'message' => 'Gói thời gian không hợp lệ hoặc không thuộc khóa học này.',
                ], 422);
            }

            // Use tier price if amount not explicitly provided
            if (!$request->filled('amount')) {
                $amount = (float) $tier->price;
            }

            // Calculate expiry
            if ($tier->duration_days) {
                $expiresAt = now()->addDays($tier->duration_days);
            }
        }

        // Check if already enrolled
        $existing = Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();

        if ($existing && in_array($existing->status, ['active', 'completed']) && !$existing->isExpired()) {
            return response()->json([
                'message' => 'Học viên đã được đăng ký khóa học này rồi và vẫn còn hiệu lực.',
            ], 409);
        }

        return DB::transaction(function () use ($userId, $courseId, $amount, $note, $existing, $tier, $tierId, $expiresAt) {
            // Create or update enrollment
            if ($existing) {
                $existing->update([
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'amount_paid' => $amount,
                    'duration_tier_id' => $tierId,
                    'expires_at' => $expiresAt,
                ]);
                $enrollment = $existing;
            } else {
                $enrollment = Enrollment::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'amount_paid' => $amount,
                    'duration_tier_id' => $tierId,
                    'expires_at' => $expiresAt,
                ]);
            }

            // Create payment record for revenue tracking
            $orderCode = 'MANUAL-' . strtoupper(uniqid());
            $payment = CoursePayment::create([
                'product_type' => 'course',
                'user_id' => $userId,
                'course_id' => $courseId,
                'enrollment_id' => $enrollment->id,
                'duration_tier_id' => $tierId,
                'order_code' => $orderCode,
                'amount' => $amount,
                'original_amount' => $amount,
                'discount_amount' => 0,
                'transfer_amount' => $amount,
                'status' => 'paid',
                'paid_at' => now(),
                'raw_payload' => json_encode([
                    'type' => 'manual',
                    'admin_id' => Auth::id(),
                    'admin_name' => Auth::user()->name ?? '',
                    'note' => $note,
                    'duration_tier_id' => $tierId,
                    'expires_at' => $expiresAt?->toIso8601String(),
                    'created_at' => now()->toIso8601String(),
                ]),
            ]);

            $enrollment->load(['user', 'course', 'durationTier']);

            return response()->json([
                'message' => 'Đã thêm học viên vào khóa học thành công.',
                'enrollment' => [
                    'id' => $enrollment->id,
                    'user' => $enrollment->user ? [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                    ] : null,
                    'course' => $enrollment->course ? [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                    ] : null,
                    'amount' => $amount,
                    'order_code' => $orderCode,
                    'tier_label' => $enrollment->durationTier?->label,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            ], 201);
        });
    }

    public function stats()
    {
        $this->ensureAdmin();

        // Overview stats
        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', 'published')->count();
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('status', 'active')->count();
        $totalRevenue = CoursePayment::where('status', 'paid')->sum('transfer_amount');
        $pendingPayments = CoursePayment::where('status', 'pending')->count();

        // Revenue last 12 months
        $revenueByMonth = CoursePayment::where('status', 'paid')
            ->where('paid_at', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw('SUM(transfer_amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = [];
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[] = Carbon::now()->subMonths($i)->format('M Y');
            $revenueData[] = (float) ($revenueByMonth[$month]->total ?? 0);
        }

        // Enrollments last 12 months
        $enrollmentsByMonth = Enrollment::where('created_at', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $enrollmentsData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $enrollmentsData[] = (int) ($enrollmentsByMonth[$month]->total ?? 0);
        }

        // Top courses by enrollment
        $topCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'enrollments' => $c->enrollments_count,
            ]);

        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'user' => $e->user?->name,
                'course' => $e->course?->title,
                'status' => $e->status,
                'created_at' => $e->created_at,
            ]);

        // Enrollment status distribution
        $enrollmentStatusCounts = Enrollment::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Payment status distribution
        $paymentStatusCounts = CoursePayment::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Users last 12 months
        $usersByMonth = User::where('created_at', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $usersData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $usersData[] = (int) ($usersByMonth[$month]->total ?? 0);
        }

        return response()->json([
            'overview' => [
                'total_courses' => $totalCourses,
                'published_courses' => $publishedCourses,
                'total_users' => $totalUsers,
                'total_students' => $totalStudents,
                'total_enrollments' => $totalEnrollments,
                'active_enrollments' => $activeEnrollments,
                'total_revenue' => $totalRevenue,
                'pending_payments' => $pendingPayments,
            ],
            'charts' => [
                'months' => $months,
                'revenue' => $revenueData,
                'enrollments' => $enrollmentsData,
                'users' => $usersData,
            ],
            'top_courses' => $topCourses,
            'recent_enrollments' => $recentEnrollments,
            'enrollment_status' => $enrollmentStatusCounts,
            'payment_status' => $paymentStatusCounts,
        ]);
    }
}
