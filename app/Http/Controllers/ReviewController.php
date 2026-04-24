<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function store(Request $request, $courseId)
    {
        $course = Course::findByIdOrSlugOrFail($courseId);
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:2000',
            'reviewer_name' => $user ? 'nullable|string|max:120' : 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $payload = [
            'course_id' => $course->id,
            'rating' => (int) $data['rating'],
            'comment' => trim($data['comment']),
            'reviewer_name' => $user ? $user->name : trim($data['reviewer_name']),
            'is_approved' => false,
            'approved_at' => null,
        ];

        if ($user) {
            $review = Review::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                $payload
            );
        } else {
            $payload['user_id'] = null;
            $review = Review::create($payload);
        }

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review,
        ], 201);
    }
}
