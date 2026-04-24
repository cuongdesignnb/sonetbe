<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EbookController extends Controller
{
    public function index(Request $request)
    {
        $query = Ebook::with('category')->available();

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['ebook', 'book', 'guide'])) {
            $query->byType($request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $allowedSorts = ['created_at', 'price', 'download_count', 'title'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        $ebooks = $query->paginate($request->get('per_page', 12));

        // Hide file_url from listing
        $ebooks->getCollection()->each(function ($ebook) {
            $ebook->makeHidden('file_url');
        });

        return response()->json($ebooks);
    }

    public function show(Request $request, string $slug)
    {
        $ebook = Ebook::with('category')
            ->available()
            ->where('slug', $slug)
            ->firstOrFail();

        // Check if current user has purchased this ebook
        $hasPurchased = false;
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $hasPurchased = CoursePayment::where('user_id', $user->id)
                ->where('ebook_id', $ebook->id)
                ->where('product_type', 'ebook')
                ->where('status', 'paid')
                ->exists();
        }

        // Hide file_url unless purchased
        if (!$hasPurchased) {
            $ebook->makeHidden('file_url');
        }

        return response()->json([
            'ebook' => $ebook,
            'has_purchased' => $hasPurchased,
        ]);
    }
}
