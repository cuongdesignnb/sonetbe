<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminServiceCategoryController extends Controller
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

        $query = ServiceCategory::query();

        if ($request->has('active')) {
            $active = $request->boolean('active');
            $query->where('is_active', $active);
        }

        $items = $query->orderBy('name')->get();

        return response()->json(['data' => $items]);
    }

    public function show($id)
    {
        $this->ensureAdmin();

        $item = ServiceCategory::findOrFail($id);
        return response()->json(['category' => $item]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:service_categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (ServiceCategory::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $data['slug'] = $slug;
        }

        $category = ServiceCategory::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $category = ServiceCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:service_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('name', $data) && empty($data['slug'])) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $i = 2;
            while (ServiceCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $category = ServiceCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
