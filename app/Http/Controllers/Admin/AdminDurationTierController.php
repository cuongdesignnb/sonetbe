<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDurationTier;
use App\Models\CourseDurationTierTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminDurationTierController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * List all duration tiers for a course (including inactive), with targets.
     */
    public function index($courseId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);
        $tiers = $course->allDurationTiers()->with(['targets'])->get();

        // Enrich targets with user/group names
        $tiers->each(function ($tier) {
            $tier->targets->each(function ($target) {
                if ($target->target_type === 'user') {
                    $user = \App\Models\User::select('id', 'name', 'email')->find($target->target_id);
                    $target->target_name = $user ? $user->name : 'Deleted user';
                    $target->target_email = $user?->email;
                } elseif ($target->target_type === 'group') {
                    $group = \App\Models\UserGroup::select('id', 'name', 'color')->find($target->target_id);
                    $target->target_name = $group ? $group->name : 'Deleted group';
                    $target->target_color = $group?->color;
                }
            });
        });

        return response()->json(['data' => $tiers]);
    }

    /**
     * Create a new duration tier with optional targets.
     */
    public function store(Request $request, $courseId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);

        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'duration_days' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'targets' => 'nullable|array',
            'targets.*.target_type' => 'required_with:targets|in:user,group',
            'targets.*.target_id' => 'required_with:targets|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $targets = $data['targets'] ?? [];
        unset($data['targets']);

        $data['course_id'] = $course->id;
        $data['sort_order'] = $data['sort_order'] ?? ($course->allDurationTiers()->max('sort_order') + 1);
        $data['is_default'] = $data['is_default'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;

        return DB::transaction(function () use ($data, $targets, $course) {
            // If this tier is set as default, unset other defaults
            if ($data['is_default']) {
                CourseDurationTier::where('course_id', $course->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $tier = CourseDurationTier::create($data);

            // Create targets
            foreach ($targets as $target) {
                CourseDurationTierTarget::create([
                    'duration_tier_id' => $tier->id,
                    'target_type' => $target['target_type'],
                    'target_id' => $target['target_id'],
                ]);
            }

            $tier->load('targets');

            return response()->json([
                'message' => 'Tier created successfully',
                'tier' => $tier,
            ], 201);
        });
    }

    /**
     * Update an existing duration tier with targets.
     */
    public function update(Request $request, $courseId, $tierId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);
        $tier = CourseDurationTier::where('course_id', $course->id)->findOrFail($tierId);

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|required|string|max:100',
            'duration_days' => 'nullable|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'targets' => 'nullable|array',
            'targets.*.target_type' => 'required_with:targets|in:user,group',
            'targets.*.target_id' => 'required_with:targets|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $hasTargets = array_key_exists('targets', $data);
        $targets = $data['targets'] ?? [];
        unset($data['targets']);

        return DB::transaction(function () use ($data, $targets, $hasTargets, $tier, $course) {
            // If this tier is being set as default, unset other defaults
            if (!empty($data['is_default'])) {
                CourseDurationTier::where('course_id', $course->id)
                    ->where('id', '!=', $tier->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $tier->update($data);

            // Update targets only if explicitly provided
            if ($hasTargets) {
                // Delete existing targets and recreate
                $tier->targets()->delete();

                foreach ($targets as $target) {
                    CourseDurationTierTarget::create([
                        'duration_tier_id' => $tier->id,
                        'target_type' => $target['target_type'],
                        'target_id' => $target['target_id'],
                    ]);
                }
            }

            $tier->load('targets');

            return response()->json([
                'message' => 'Tier updated successfully',
                'tier' => $tier->fresh()->load('targets'),
            ]);
        });
    }

    /**
     * Delete a duration tier.
     */
    public function destroy($courseId, $tierId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);
        $tier = CourseDurationTier::where('course_id', $course->id)->findOrFail($tierId);

        // Check if any active enrollments use this tier
        $activeEnrollments = $tier->enrollments()
            ->where('status', 'active')
            ->count();

        if ($activeEnrollments > 0) {
            return response()->json([
                'message' => "Không thể xoá: có {$activeEnrollments} học viên đang sử dụng gói này.",
            ], 422);
        }

        $tier->delete();

        return response()->json(['message' => 'Tier deleted successfully']);
    }

    /**
     * Reorder tiers.
     */
    public function reorder(Request $request, $courseId)
    {
        $this->ensureAdmin();

        $course = Course::findOrFail($courseId);

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('orders') as $item) {
            CourseDurationTier::where('course_id', $course->id)
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Tiers reordered successfully']);
    }
}
