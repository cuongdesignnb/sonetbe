<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webinar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminWebinarController extends Controller
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

        $query = Webinar::withCount('registrations');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('instructor_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('scheduled_at', 'desc');

        return response()->json($query->paginate(15));
    }

    public function show(int $id)
    {
        $this->ensureAdmin();

        $webinar = Webinar::withCount('registrations')->findOrFail($id);
        return response()->json($webinar);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'instructor_name' => 'required|string|max:255',
            'instructor_avatar' => 'nullable|string|max:500',
            'zoom_link' => 'nullable|string|max:500',
            'replay_url' => 'nullable|string|max:500',
            'replay_bunny_id' => 'nullable|string|max:100',
            'replay_bunny_library_id' => 'nullable|string|max:50',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:upcoming,live,completed,cancelled',
            'is_free' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:500',
            'speakers' => 'nullable|array',
            'speakers.*.name' => 'required_with:speakers|string|max:255',
            'speakers.*.role' => 'nullable|string|max:255',
            'speakers.*.avatar' => 'nullable|string|max:500',
            'max_attendees' => 'nullable|integer|min:1',
            'fake_registrations' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $webinar = Webinar::create($data);

        return response()->json($webinar, 201);
    }

    public function update(Request $request, int $id)
    {
        $this->ensureAdmin();

        $webinar = Webinar::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'instructor_name' => 'sometimes|required|string|max:255',
            'instructor_avatar' => 'nullable|string|max:500',
            'zoom_link' => 'nullable|string|max:500',
            'replay_url' => 'nullable|string|max:500',
            'replay_bunny_id' => 'nullable|string|max:100',
            'replay_bunny_library_id' => 'nullable|string|max:50',
            'scheduled_at' => 'sometimes|required|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:upcoming,live,completed,cancelled',
            'is_free' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:500',
            'speakers' => 'nullable|array',
            'speakers.*.name' => 'required_with:speakers|string|max:255',
            'speakers.*.role' => 'nullable|string|max:255',
            'speakers.*.avatar' => 'nullable|string|max:500',
            'max_attendees' => 'nullable|integer|min:1',
            'fake_registrations' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $webinar->update($validator->validated());

        return response()->json($webinar);
    }

    public function destroy(int $id)
    {
        $this->ensureAdmin();

        $webinar = Webinar::findOrFail($id);
        $webinar->delete();

        return response()->json(['message' => 'Webinar deleted']);
    }

    public function registrations(Request $request, int $id)
    {
        $this->ensureAdmin();

        $webinar = Webinar::findOrFail($id);

        $query = $webinar->registrations()->with('user:id,name,email');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(20));
    }

    public function allRegistrations(Request $request)
    {
        $this->ensureAdmin();

        $query = \App\Models\WebinarRegistration::with([
            'user:id,name,email,phone',
            'webinar:id,title,slug,scheduled_at,status',
        ]);

        if ($request->has('webinar_id') && $request->webinar_id) {
            $query->where('webinar_id', $request->webinar_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $registrations = $query->paginate($request->get('per_page', 20));

        // Also return the list of webinars for the filter dropdown
        $webinars = Webinar::select('id', 'title')->orderBy('scheduled_at', 'desc')->get();

        return response()->json([
            'registrations' => $registrations,
            'webinars' => $webinars,
        ]);
    }

    public function updateRegistration(Request $request, int $id)
    {
        $this->ensureAdmin();

        $registration = \App\Models\WebinarRegistration::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:registered,attended,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $registration->update(['status' => $request->status]);

        return response()->json($registration->load(['user:id,name,email', 'webinar:id,title']));
    }

    public function deleteRegistration(int $id)
    {
        $this->ensureAdmin();

        $registration = \App\Models\WebinarRegistration::findOrFail($id);
        $registration->delete();

        return response()->json(['message' => 'Đã xóa đăng ký']);
    }
}
