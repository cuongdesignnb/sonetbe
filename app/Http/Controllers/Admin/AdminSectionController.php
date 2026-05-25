<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminSectionController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function index($courseId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);

        return response()->json([
            'sections' => $course->sections()->with('lessons')->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request, $courseId)
    {
        $this->ensureAdmin();

        Course::findOrFail($courseId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'is_sellable' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['course_id'] = (int) $courseId;

        $section = CourseSection::create($data);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $section = CourseSection::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'is_sellable' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $section->update($validator->validated());

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $section = CourseSection::findOrFail($id);
        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }

    public function reorder(Request $request, $courseId)
    {
        $this->ensureAdmin();

        Course::findOrFail($courseId);

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:course_sections,id',
            'items.*.order' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('items') as $item) {
            CourseSection::where('id', $item['id'])->where('course_id', $courseId)->update([
                'order' => $item['order'],
            ]);
        }

        return response()->json(['message' => 'Reordered successfully']);
    }
}
