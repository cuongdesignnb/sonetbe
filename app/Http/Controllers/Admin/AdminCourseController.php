<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCourseController extends Controller
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

        $query = Course::with(['instructor', 'category'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function options()
    {
        $this->ensureAdmin();

        $courses = Course::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json(['data' => $courses]);
    }

    public function show($id)
    {
        $this->ensureAdmin();

        $course = Course::with([
            'instructor',
            'category',
            'sections' => function ($q) {
                $q->orderBy('order');
            },
            'sections.lessons' => function ($q) {
                $q->orderBy('order');
            },
            'lessons' => function ($q) {
                $q->orderBy('order');
            },
            'allDurationTiers',
        ])->findOrFail($id);

        return response()->json(['course' => $course]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'status' => 'nullable|in:draft,published,archived,coming_soon',
            'thumbnail' => 'nullable|string|max:2048',
            'preview_video' => 'nullable|string|max:2048',
            'meta_description' => 'nullable|string',
            'marketing' => 'nullable|array',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['instructor_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        // Auto-generate slug from title if not provided
        if (empty($data['slug'])) {
            $base = Str::slug($data['title']) ?: 'course';
            $slug = $base;
            $i = 2;
            while (Course::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $course = Course::create($data);

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load('instructor', 'category'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'status' => 'sometimes|required|in:draft,published,archived,coming_soon',
            'thumbnail' => 'nullable|string|max:2048',
            'preview_video' => 'nullable|string|max:2048',
            'meta_description' => 'nullable|string',
            'marketing' => 'nullable|array',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Merge marketing instead of replacing so Landing Page sections aren't lost
        if (array_key_exists('marketing', $validated) && is_array($validated['marketing'])) {
            $existing = $course->marketing ?? [];
            $validated['marketing'] = array_merge($existing, $validated['marketing']);
        }

        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load('instructor', 'category'),
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    public function duplicate($id)
    {
        $this->ensureAdmin();

        $original = Course::findOrFail($id);

        $clone = $original->replicate();
        $clone->title = $original->title . ' (Bản sao)';
        $clone->slug = $original->slug ? $original->slug . '-copy-' . time() : null;
        $clone->status = 'draft';
        $clone->sort_order = 0;
        $clone->save();

        // Duplicate sections and lessons
        foreach ($original->sections()->orderBy('order')->get() as $section) {
            $newSection = $section->replicate();
            $newSection->course_id = $clone->id;
            $newSection->save();

            foreach ($section->lessons()->orderBy('order')->get() as $lesson) {
                $newLesson = $lesson->replicate();
                $newLesson->section_id = $newSection->id;
                $newLesson->course_id = $clone->id;
                $newLesson->save();
            }
        }

        return response()->json([
            'message' => 'Course duplicated successfully',
            'course' => $clone->load('instructor', 'category'),
        ], 201);
    }

    public function reorder(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:courses,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('orders') as $item) {
            Course::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Courses reordered successfully']);
    }
}

