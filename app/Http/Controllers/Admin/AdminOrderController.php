<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoursePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderController extends Controller
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

        $query = CoursePayment::with(['user', 'course', 'ebook']);

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('created_at', 'desc');

        $paginated = $query->paginate($request->integer('per_page', 20));

        // Stats
        $stats = [
            'total' => CoursePayment::count(),
            'paid' => CoursePayment::where('status', 'paid')->count(),
            'pending' => CoursePayment::where('status', 'pending')->count(),
            'course_orders' => CoursePayment::where('product_type', 'course')->count(),
            'ebook_orders' => CoursePayment::where('product_type', 'ebook')->count(),
            'total_revenue' => CoursePayment::where('status', 'paid')->sum('amount'),
        ];

        return response()->json([
            'orders' => $paginated,
            'stats' => $stats,
        ]);
    }
}
