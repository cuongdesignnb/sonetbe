<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $flat = $request->boolean('flat');

        if ($flat) {
            $categories = Category::query()
                ->when($request->boolean('active', true), fn ($q) => $q->active())
                ->withCount(['courses' => function ($q) {
                    $q->published();
                }])
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        }

        $categories = Category::active()
            ->with(['children' => function ($q) {
                $q->active()->orderBy('name');
            }])
            ->withCount(['courses' => function ($q) {
                $q->published();
            }])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::with([
            'children',
            'courses' => function ($q) {
                $q->published()->with(['instructor', 'category']);
            },
        ])
            ->findOrFail($id);

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable',
        ]);

        $data = $request->all();

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif (isset($data['image']) && is_string($data['image']) && trim($data['image']) === '') {
            $data['image'] = null;
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif (array_key_exists('image', $data) && is_string($data['image']) && trim($data['image']) === '') {
            $data['image'] = null;
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}