<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminCourseFaqController extends Controller
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

        $courseIdRaw = $request->get('course_id');
        if ($courseIdRaw === null || $courseIdRaw === '') {
            return response()->json(['data' => []]);
        }

        $courseId = (int) $courseIdRaw;

        $query = CourseFaq::query();
        if ($courseId <= 0) {
            $query->whereNull('course_id');
        } else {
            $query->where('course_id', $courseId);
        }

        $items = $query
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'course_id' => 'nullable|exists:courses,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faq = CourseFaq::create($validator->validated());

        return response()->json([
            'message' => 'FAQ created successfully',
            'faq' => $faq,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $faq = CourseFaq::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|required|string|max:255',
            'answer' => 'sometimes|required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faq->update($validator->validated());

        return response()->json([
            'message' => 'FAQ updated successfully',
            'faq' => $faq,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $faq = CourseFaq::findOrFail($id);
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted successfully']);
    }
}
