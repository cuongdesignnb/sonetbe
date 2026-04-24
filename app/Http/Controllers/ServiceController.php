<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\ServicePost;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ServicePost::with('category')
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
        $post = ServicePost::with('category')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = ServicePost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, function ($q) use ($post) {
                $q->where('category_id', $post->category_id);
            })
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'published_at']);

        return response()->json(['post' => $post, 'related' => $related]);
    }

    public function categories()
    {
        $categories = ServiceCategory::active()
            ->withCount(['posts' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }
}
