<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminServicePostController extends Controller
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

        $query = ServicePost::with(['category', 'author'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $items = $query->get();

        return response()->json(['data' => $items]);
    }

    public function show($id)
    {
        $this->ensureAdmin();

        $post = ServicePost::with(['category', 'author'])->findOrFail($id);
        return response()->json(['post' => $post]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:service_posts,slug',
            'category_id' => 'nullable|exists:service_categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|string|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data['slug'])) {
            $base = Str::slug($data['title']);
            $slug = $base;
            $i = 2;
            while (ServicePost::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $data['slug'] = $slug;
        }

        $data['author_id'] = Auth::id();

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        $post = ServicePost::create($data);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $post = ServicePost::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:service_posts,slug,' . $post->id,
            'category_id' => 'nullable|exists:service_categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'featured_image' => 'nullable|string|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('title', $data) && empty($data['slug'])) {
            $base = Str::slug($data['title']);
            $slug = $base;
            $i = 2;
            while (ServicePost::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $data['slug'] = $slug;
        }

        if (array_key_exists('status', $data)) {
            if ($data['status'] === 'published' && !$post->published_at) {
                $data['published_at'] = now();
            }
            if ($data['status'] !== 'published') {
                $data['published_at'] = null;
            }
        }

        $post->update($data);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $post = ServicePost::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
