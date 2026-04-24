<?php

namespace App\Http\Controllers;

use App\Models\CourseFaq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $courseIdRaw = $request->get('course_id');
        $query = CourseFaq::query()->where('is_active', true);

        if ($courseIdRaw !== null && $courseIdRaw !== '') {
            $courseId = (int) $courseIdRaw;
            if ($courseId > 0) {
                $query->where('course_id', $courseId);
            } else {
                $query->whereNull('course_id');
            }
        } else {
            $query->whereNull('course_id');
        }

        $items = $query->orderBy('order')->orderBy('id')->get();

        return response()->json(['data' => $items]);
    }
}
