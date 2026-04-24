<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminReviewController extends Controller
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

        $query = Review::with(['user', 'course'])->orderByDesc('created_at');

        $courseId = $request->integer('course_id');
        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $status = $request->string('status')->toString();
        if ($status === 'approved') {
            $query->where('is_approved', true);
        } elseif ($status === 'pending') {
            $query->where('is_approved', false);
        }

        $items = $query->get();

        return response()->json(['data' => $items]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $review = Review::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'sometimes|required|string|min:5|max:2000',
            'reviewer_name' => 'nullable|string|max:120',
            'is_approved' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('comment', $data)) {
            $data['comment'] = trim($data['comment']);
        }
        if (array_key_exists('reviewer_name', $data)) {
            $data['reviewer_name'] = $data['reviewer_name'] ? trim($data['reviewer_name']) : null;
        }

        if (array_key_exists('is_approved', $data)) {
            $data['approved_at'] = $data['is_approved'] ? now() : null;
        }

        $review->update($data);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->load(['user', 'course']),
        ]);
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}
