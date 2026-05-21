<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use App\Models\UserGroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminUserGroupController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * List all user groups with member count.
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $query = UserGroup::query()
            ->withCount('members');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $groups = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $groups]);
    }

    /**
     * Create a new user group.
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:user_groups',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $group = UserGroup::create($data);

        return response()->json([
            'message' => 'Tạo nhóm thành công.',
            'group' => $group->loadCount('members'),
        ], 201);
    }

    /**
     * Show a single group with members.
     */
    public function show($id)
    {
        $this->ensureAdmin();

        $group = UserGroup::withCount('members')->findOrFail($id);

        return response()->json(['group' => $group]);
    }

    /**
     * Update a user group.
     */
    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $group = UserGroup::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:user_groups,name,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group->update($validator->validated());

        return response()->json([
            'message' => 'Cập nhật nhóm thành công.',
            'group' => $group->fresh()->loadCount('members'),
        ]);
    }

    /**
     * Delete a user group.
     */
    public function destroy($id)
    {
        $this->ensureAdmin();

        $group = UserGroup::findOrFail($id);

        // Check if any duration tier targets reference this group
        $tierTargetCount = \App\Models\CourseDurationTierTarget::where('target_type', 'group')
            ->where('target_id', $group->id)
            ->count();

        if ($tierTargetCount > 0) {
            return response()->json([
                'message' => "Không thể xóa: nhóm đang được sử dụng trong {$tierTargetCount} mốc thời gian khóa học.",
            ], 422);
        }

        $group->delete();

        return response()->json(['message' => 'Xóa nhóm thành công.']);
    }

    /**
     * List members of a group.
     */
    public function members($id)
    {
        $this->ensureAdmin();

        $group = UserGroup::findOrFail($id);

        $members = $group->users()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.role')
            ->orderBy('user_group_members.created_at', 'desc')
            ->get();

        return response()->json(['data' => $members]);
    }

    /**
     * Add users to a group.
     */
    public function addMembers(Request $request, $id)
    {
        $this->ensureAdmin();

        $group = UserGroup::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userIds = $request->input('user_ids');
        $added = 0;

        foreach ($userIds as $userId) {
            $exists = UserGroupMember::where('user_group_id', $group->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                UserGroupMember::create([
                    'user_group_id' => $group->id,
                    'user_id' => $userId,
                ]);
                $added++;
            }
        }

        return response()->json([
            'message' => "Đã thêm {$added} thành viên vào nhóm.",
            'added' => $added,
        ]);
    }

    /**
     * Remove users from a group.
     */
    public function removeMembers(Request $request, $id)
    {
        $this->ensureAdmin();

        $group = UserGroup::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = UserGroupMember::where('user_group_id', $group->id)
            ->whereIn('user_id', $request->input('user_ids'))
            ->delete();

        return response()->json([
            'message' => "Đã xóa {$deleted} thành viên khỏi nhóm.",
            'removed' => $deleted,
        ]);
    }
}
