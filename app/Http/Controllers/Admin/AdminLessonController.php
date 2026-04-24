<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminLessonController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function index(Request $request, $courseId)
    {
        $this->ensureAdmin();

        Course::findOrFail($courseId);

        $query = Lesson::where('lessons.course_id', $courseId)
            ->leftJoin('course_sections', 'lessons.section_id', '=', 'course_sections.id')
            ->select('lessons.*')
            ->orderBy('course_sections.order')
            ->orderBy('lessons.order');
        if ($request->filled('section_id')) {
            $query->where('lessons.section_id', $request->integer('section_id'));
        }

        return response()->json([
            'lessons' => $query->get(),
        ]);
    }

    public function store(Request $request, $courseId)
    {
        $this->ensureAdmin();

        Course::findOrFail($courseId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'content' => 'nullable|string',
            'order' => 'required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'section_id' => 'nullable|integer|exists:course_sections,id',
            'embed_url' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['section_id'])) {
            $section = CourseSection::findOrFail($data['section_id']);
            if ((int) $section->course_id !== (int) $courseId) {
                return response()->json(['message' => 'Invalid section for course'], 422);
            }
        }

        $data['course_id'] = (int) $courseId;
        $data['is_preview'] = $request->boolean('is_preview', false);

        if (array_key_exists('embed_url', $data)) {
            $data['embed_url'] = trim((string) ($data['embed_url'] ?? ''));
            if ($data['embed_url'] === '') {
                $data['embed_url'] = null;
            } else {
                $data['video_bunny_id'] = null;
                $data['video_bunny_library_id'] = null;
                $data['video_local_path'] = null;
                $data['video_url'] = null;
            }
        }

        $lesson = Lesson::create($data);

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $lesson = Lesson::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'content' => 'nullable|string',
            'order' => 'sometimes|required|integer|min:1',
            'duration' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'section_id' => 'nullable|integer|exists:course_sections,id',
            'video_bunny_id' => 'nullable|string|max:255',
            'video_bunny_library_id' => 'nullable|string|max:255',
            'embed_url' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('section_id', $data) && !empty($data['section_id'])) {
            $section = CourseSection::findOrFail($data['section_id']);
            if ((int) $section->course_id !== (int) $lesson->course_id) {
                return response()->json(['message' => 'Invalid section for course'], 422);
            }
        }

        if (array_key_exists('video_bunny_id', $data)) {
            $data['video_bunny_id'] = $data['video_bunny_id'] ?: null;
            if (!empty($data['video_bunny_id'])) {
                $data['video_local_path'] = null;
                $data['embed_url'] = null;
            } else {
                $data['video_bunny_library_id'] = null;
            }
        }

        if (array_key_exists('embed_url', $data)) {
            $data['embed_url'] = trim((string) ($data['embed_url'] ?? ''));
            if ($data['embed_url'] === '') {
                $data['embed_url'] = null;
            } else {
                $data['video_bunny_id'] = null;
                $data['video_bunny_library_id'] = null;
                $data['video_local_path'] = null;
                $data['video_url'] = null;
            }
        }

        $lesson->update($data);

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson,
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully']);
    }
}
