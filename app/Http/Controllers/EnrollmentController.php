<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function enroll(Request $request, $courseId)
    {
        $course = Course::findByIdOrSlugOrFail($courseId);
        $user = Auth::user();

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Already enrolled in this course'
            ], 400);
        }

        // For free courses, enroll directly
        if ($course->price == 0) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
                'enrolled_at' => now(),
                'amount_paid' => 0
            ]);

            return response()->json([
                'message' => 'Enrolled successfully',
                'enrollment' => $enrollment
            ]);
        }

        // For paid courses, create pending enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'pending',
            'amount_paid' => $course->price
        ]);

        // TODO: Integrate with Stripe payment
        // Return payment intent or checkout URL

        return response()->json([
            'message' => 'Payment required',
            'enrollment' => $enrollment,
            'payment_amount' => $course->price
        ]);
    }

    public function myEnrollments()
    {
        $enrollments = Enrollment::with(['course.instructor', 'course.category', 'course.lessons'])
            ->whereHas('course')
            ->where('user_id', Auth::id())
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('enrolled_at', 'desc')
            ->paginate(12);

        // Add progress for each enrollment
        foreach ($enrollments as $enrollment) {
            $progress = Auth::user()->getCourseProgress($enrollment->course_id);
            $enrollment->progress = $progress;
            $lessonIds = $enrollment->course?->lessons?->pluck('id') ?? collect();
            $totalLessons = $lessonIds->count();
            $completedLessons = 0;
            $watchedDuration = 0;

            if ($totalLessons > 0) {
                $completedLessons = LessonProgress::where('user_id', Auth::id())
                    ->whereIn('lesson_id', $lessonIds)
                    ->where('completed', true)
                    ->count();
                $watchedDuration = (int) LessonProgress::where('user_id', Auth::id())
                    ->whereIn('lesson_id', $lessonIds)
                    ->sum('watched_duration');
            }

            $enrollment->total_lessons = $totalLessons;
            $enrollment->completed_lessons = $completedLessons;
            $enrollment->watched_duration = $watchedDuration;
        }

        return response()->json($enrollments);
    }

    public function getCourseProgress($courseId)
    {
        if (is_numeric($courseId)) {
            $course = Course::with('lessons')->findOrFail($courseId);
        } else {
            $course = Course::with('lessons')->where('slug', $courseId)->firstOrFail();
        }
        $user = Auth::user();

        // Check if enrolled — always use numeric course ID
        if (!$user->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'Not enrolled'], 403);
        }

        $lessons = $course->lessons;
        $progress = [];

        foreach ($lessons as $lesson) {
            $lessonProgress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            $progress[] = [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'completed' => $lessonProgress ? $lessonProgress->completed : false,
                'completion_percentage' => $lessonProgress ? $lessonProgress->completion_percentage : 0,
                'watched_duration' => $lessonProgress ? $lessonProgress->watched_duration : 0
            ];
        }

        $totalProgress = $user->getCourseProgress($course->id);

        return response()->json([
            'course' => $course,
            'lessons_progress' => $progress,
            'total_progress' => $totalProgress
        ]);
    }

    public function updateLessonProgress(Request $request, $lessonId)
    {
        $request->validate([
            'completion_percentage' => 'required|integer|min:0|max:100',
            'watched_duration' => 'required|integer|min:0'
        ]);

        $user = Auth::user();
        $lesson = \App\Models\Lesson::findOrFail($lessonId);

        // Check if enrolled in the course
        if (!$user->isEnrolledIn($lesson->course_id)) {
            return response()->json(['message' => 'Not enrolled'], 403);
        }

        $newPercentage = $request->completion_percentage;
        $newWatchedDuration = $request->watched_duration;

        // Get existing progress
        $progress = LessonProgress::firstOrNew([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        // Only update if new values are higher (never go backward)
        $progress->completion_percentage = max(
            $progress->completion_percentage ?? 0,
            $newPercentage
        );
        $progress->watched_duration = max(
            $progress->watched_duration ?? 0,
            $newWatchedDuration
        );

        // Mark as completed if >= 80%, never un-complete
        if ($progress->completion_percentage >= 80 && !$progress->completed) {
            $progress->completed = true;
            $progress->completed_at = now();
        }

        $progress->save();

        // Check if course is completed
        $courseProgress = $user->getCourseProgress($lesson->course_id);
        if ($courseProgress >= 100) {
            Enrollment::where('user_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
        }

        return response()->json([
            'message' => 'Progress updated',
            'progress' => $progress,
            'course_progress' => $courseProgress
        ]);
    }
}