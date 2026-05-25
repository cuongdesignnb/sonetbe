<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'category'])
            ->withAvg([
                'reviews as reviews_avg_rating' => function ($q) {
                    $q->where('is_approved', true);
                }
            ], 'rating')
            ->withCount('enrollments')
            ->withSum('lessons', 'duration')
            ->visible();

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by level
        if ($request->has('level')) {
            $query->byLevel($request->level);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $allowedSorts = ['created_at', 'price', 'title', 'sort_order'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'sort_order';
        }
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        $courses = $query->paginate(12);

        // Add price range for courses with duration tiers
        foreach ($courses as $course) {
            $course->setAttribute('price_range', $course->priceRange());
        }

        return response()->json($courses);
    }

    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'category',
            'faqs' => function ($query) {
                $query->where('is_active', true)->orderBy('order')->orderBy('id');
            },
            'sections' => function ($query) {
                $query->orderBy('order')->with(['lessons' => function ($q) {
                    $q->orderBy('order');
                }]);
            },
            'lessons' => function ($query) {
                $query->orderBy('order');
            },
            'reviews' => function ($query) {
                $query->where('is_approved', true)->orderByDesc('created_at');
            },
            'reviews.user',
            'durationTiers',
        ]);

        if (is_numeric($id)) {
            $course = $course->findOrFail($id);
        } else {
            $course = $course->where('slug', $id)->firstOrFail();
        }

        // Check if user is enrolled (resolve user from bearer token on public route)
        $isEnrolled = false;
        $user = Auth::user();
        if (!$user) {
            $token = request()->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }
        if ($user && method_exists($user, 'isEnrolledIn')) {
            $isEnrolled = $user->isEnrolledIn($course->id);
            if ($course->sections) {
                foreach ($course->sections as $section) {
                    $section->setAttribute('is_enrolled', $user->hasAccessToSection($section->id));
                }
            }
        }

        // Filter duration tiers based on user permissions
        $filteredTiers = $course->durationTiers->filter(function ($tier) use ($user) {
            if (!$user) {
                // Guest users only see non-targeted tiers
                return !$tier->isTargeted();
            }
            return $tier->isAvailableForUser($user->id);
        })->values();

        // Replace loaded tiers with filtered ones
        $course->setRelation('durationTiers', $filteredTiers);

        return response()->json([
            'course' => $course,
            'is_enrolled' => $isEnrolled,
            'price_range' => $course->priceRange(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'marketing' => 'nullable|array',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['instructor_id'] = Auth::id();
        $data['status'] = 'draft';

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $path = $thumbnail->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $course = Course::create($data);

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load('instructor', 'category')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'status' => 'sometimes|required|in:draft,published,archived',
            'marketing' => 'nullable|array',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $path = $thumbnail->store('thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $course->update($data);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->load('instructor', 'category')
        ]);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Check if user owns this course
        if ($course->instructor_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }

    public function myCourses()
    {
        $courses = Course::with(['category', 'lessons'])
            ->where('instructor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($courses);
    }
}