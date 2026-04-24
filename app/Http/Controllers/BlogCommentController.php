<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    public function indexBySlug($slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $comments = BlogComment::with('user')
            ->where('blog_post_id', $post->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function storeBySlug(Request $request, $slug)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|min:3|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $comment = BlogComment::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
            'comment' => trim($data['comment']),
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => $comment->load('user'),
        ], 201);
    }
}

