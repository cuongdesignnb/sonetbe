<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminEbookController extends Controller
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

        $query = Ebook::with('category');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(15));
    }

    public function show(int $id)
    {
        $this->ensureAdmin();

        $ebook = Ebook::with('category')->findOrFail($id);
        return response()->json($ebook);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'author_name' => 'required|string|max:255',
            'file_url' => 'nullable|string|max:500',
            'preview_url' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'total_pages' => 'nullable|integer|min:1',
            'type' => 'required|in:ebook,book,guide',
            'status' => 'required|in:draft,published,coming_soon,archived',
            'features' => 'nullable|array',
            'tags' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $ebook = Ebook::create($data);

        return response()->json($ebook->load('category'), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->ensureAdmin();

        $ebook = Ebook::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'author_name' => 'sometimes|required|string|max:255',
            'file_url' => 'nullable|string|max:500',
            'preview_url' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'total_pages' => 'nullable|integer|min:1',
            'type' => 'sometimes|required|in:ebook,book,guide',
            'status' => 'sometimes|required|in:draft,published,coming_soon,archived',
            'features' => 'nullable|array',
            'tags' => 'nullable|array',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ebook->update($validator->validated());

        return response()->json($ebook->load('category'));
    }

    public function destroy(int $id)
    {
        $this->ensureAdmin();

        $ebook = Ebook::findOrFail($id);
        $ebook->delete();

        return response()->json(['message' => 'Ebook deleted']);
    }
}
