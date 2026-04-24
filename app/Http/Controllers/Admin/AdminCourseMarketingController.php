<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCourseMarketingController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * List of valid marketing section keys.
     */
    private const SECTION_KEYS = [
        'promo',
        'hero',
        'stats',
        'workflow',
        'what_you_learn',
        'landing_nav',
        'ticker_texts',
        'pain_point',
        'benefits',
        'target_audience',
        'before_after',
        'instructor_extra',
        'team',
        'curriculum',
        'testimonials',
        'final_cta',
        'urgency',
        'floating_bar',
    ];

    /**
     * GET /api/admin/courses/{id}/marketing
     * Retrieve the full marketing config for a course.
     */
    public function show($id)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($id);
        $marketing = $course->marketing ?? [];

        return response()->json([
            'course_id' => $course->id,
            'course_title' => $course->title,
            'marketing' => $marketing,
            'available_sections' => self::SECTION_KEYS,
        ]);
    }

    /**
     * PUT /api/admin/courses/{id}/marketing
     * Replace the entire marketing config.
     */
    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($id);

        $request->validate([
            'marketing' => 'required|array',
        ]);

        $course->marketing = $request->input('marketing');
        $course->save();

        return response()->json([
            'message' => 'Marketing updated successfully',
            'course_id' => $course->id,
            'marketing' => $course->marketing,
        ]);
    }

    /**
     * PATCH /api/admin/courses/{id}/marketing
     * Merge-update specific sections only (deep merge at top level).
     * Send only the keys you want to update; other keys are preserved.
     */
    public function patch(Request $request, $id)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($id);

        $request->validate([
            'marketing' => 'required|array',
        ]);

        $existing = $course->marketing ?? [];
        $incoming = $request->input('marketing');

        // Merge: incoming keys overwrite, existing keys not in incoming are preserved
        $merged = array_merge($existing, $incoming);

        $course->marketing = $merged;
        $course->save();

        return response()->json([
            'message' => 'Marketing sections updated successfully',
            'course_id' => $course->id,
            'marketing' => $course->marketing,
        ]);
    }

    /**
     * GET /api/admin/courses/{id}/marketing/{section}
     * Get a specific marketing section.
     */
    public function showSection($id, $section)
    {
        $this->ensureAdmin();

        if (!in_array($section, self::SECTION_KEYS, true)) {
            return response()->json(['message' => "Invalid section: {$section}"], 422);
        }

        $course = Course::findOrFail($id);
        $marketing = $course->marketing ?? [];

        return response()->json([
            'course_id' => $course->id,
            'section' => $section,
            'data' => $marketing[$section] ?? null,
        ]);
    }

    /**
     * PUT /api/admin/courses/{id}/marketing/{section}
     * Update a specific marketing section.
     */
    public function updateSection(Request $request, $id, $section)
    {
        $this->ensureAdmin();

        if (!in_array($section, self::SECTION_KEYS, true)) {
            return response()->json(['message' => "Invalid section: {$section}"], 422);
        }

        $course = Course::findOrFail($id);

        $request->validate([
            'data' => 'present',
        ]);

        $marketing = $course->marketing ?? [];
        $marketing[$section] = $request->input('data');
        $course->marketing = $marketing;
        $course->save();

        return response()->json([
            'message' => "Section '{$section}' updated successfully",
            'course_id' => $course->id,
            'section' => $section,
            'data' => $course->marketing[$section],
        ]);
    }

    /**
     * DELETE /api/admin/courses/{id}/marketing/{section}
     * Remove a specific marketing section.
     */
    public function deleteSection($id, $section)
    {
        $this->ensureAdmin();

        if (!in_array($section, self::SECTION_KEYS, true)) {
            return response()->json(['message' => "Invalid section: {$section}"], 422);
        }

        $course = Course::findOrFail($id);

        $marketing = $course->marketing ?? [];
        unset($marketing[$section]);
        $course->marketing = $marketing;
        $course->save();

        return response()->json([
            'message' => "Section '{$section}' removed successfully",
            'course_id' => $course->id,
        ]);
    }
}
