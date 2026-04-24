<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function debug()
    {
        $totalPosts = BlogPost::count();
        $publishedPosts = BlogPost::published()->count();
        $totalCategories = BlogCategory::count();

        return response()->json([
            'status' => 'ok',
            'posts' => [
                'total' => $totalPosts,
                'published' => $publishedPosts,
            ],
            'categories' => [
                'total' => $totalCategories,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = BlogPost::with('category')
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $category = $request->string('category')->toString();
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        $posts = $query->paginate(9);

        return response()->json($posts);
    }

    public function show($slug)
    {
        $post = BlogPost::with('category')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['post' => $post]);
    }

    public function categories()
    {
        $categories = BlogCategory::active()
            ->withCount(['posts' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }
}
