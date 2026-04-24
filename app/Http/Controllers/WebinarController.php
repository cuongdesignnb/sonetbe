<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use App\Models\CoursePayment;
use App\Mail\WebinarRegistrationMail;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WebinarController extends Controller
{
    public function index(Request $request)
    {
        $query = Webinar::public()->withCount('registrations');

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['upcoming', 'completed'])) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } else {
                $query->completed();
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('instructor_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'scheduled_at');
        $allowedSorts = ['scheduled_at', 'created_at', 'views_count', 'title'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'scheduled_at';
        }
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        $webinars = $query->paginate($request->get('per_page', 12));

        return response()->json($webinars);
    }

    public function show(string $slug)
    {
        $webinar = Webinar::public()
            ->withCount('registrations')
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment views
        $webinar->increment('views_count');

        // Check if current user is registered & has paid
        $isRegistered = false;
        $hasPaid = false;
        $userId = null;

        if (Auth::guard('sanctum')->check()) {
            $userId = Auth::guard('sanctum')->id();

            $isRegistered = WebinarRegistration::where('webinar_id', $webinar->id)
                ->where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->exists();

            // Check if user has paid for this webinar
            if (!$webinar->is_free && (float) $webinar->price > 0) {
                $hasPaid = CoursePayment::where('user_id', $userId)
                    ->where('webinar_id', $webinar->id)
                    ->where('product_type', 'webinar')
                    ->where('status', 'paid')
                    ->exists();
            } else {
                $hasPaid = true; // Free webinars are always "paid"
            }
        }

        // For paid webinars, hide zoom_link and replay_url if user hasn't paid
        $webinarData = $webinar->toArray();
        if (!$webinar->is_free && (float) $webinar->price > 0 && !$hasPaid) {
            $webinarData['zoom_link'] = null;
            $webinarData['replay_url'] = null;
            $webinarData['replay_bunny_id'] = null;
            $webinarData['replay_bunny_library_id'] = null;
        }

        // Override registrations_count with fake if set
        if ($webinar->fake_registrations !== null) {
            $webinarData['registrations_count'] = $webinar->fake_registrations;
        }

        return response()->json([
            'webinar' => $webinarData,
            'is_registered' => $isRegistered,
            'has_paid' => $hasPaid,
        ]);
    }

    public function register(Request $request, string $slug)
    {
        $webinar = Webinar::public()->where('slug', $slug)->firstOrFail();

        if ($webinar->status !== 'upcoming') {
            return response()->json(['message' => 'Webinar này đã kết thúc hoặc bị hủy.'], 422);
        }

        // Paid webinars require payment first
        if (!$webinar->is_free && (float) $webinar->price > 0) {
            $user = $request->user();
            $hasPaid = CoursePayment::where('user_id', $user->id)
                ->where('webinar_id', $webinar->id)
                ->where('product_type', 'webinar')
                ->where('status', 'paid')
                ->exists();

            if (!$hasPaid) {
                return response()->json(['message' => 'Vui lòng thanh toán trước khi đăng ký webinar có phí.'], 422);
            }
        }

        if ($webinar->max_attendees) {
            $currentCount = $webinar->registrations()->where('status', '!=', 'cancelled')->count();
            if ($currentCount >= $webinar->max_attendees) {
                return response()->json(['message' => 'Webinar đã đủ số lượng đăng ký.'], 422);
            }
        }

        $user = $request->user();

        $existing = WebinarRegistration::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'cancelled') {
                $existing->update(['status' => 'registered']);

                // Send webinar link email on re-registration
                try {
                    MailConfigService::applyFromSettings();
                    Mail::to($user->email)->send(new WebinarRegistrationMail($webinar, $user));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send webinar registration email: ' . $e->getMessage());
                }

                return response()->json(['message' => 'Đăng ký lại thành công!', 'registration' => $existing]);
            }
            return response()->json(['message' => 'Bạn đã đăng ký webinar này rồi.'], 422);
        }

        $registration = WebinarRegistration::create([
            'webinar_id' => $webinar->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        // Send webinar link email
        try {
            MailConfigService::applyFromSettings();
            Mail::to($user->email)->send(new WebinarRegistrationMail($webinar, $user));
        } catch (\Throwable $e) {
            Log::warning('Failed to send webinar registration email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đăng ký webinar thành công!',
            'registration' => $registration,
        ], 201);
    }

    public function guestRegister(Request $request, string $slug)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $webinar = Webinar::public()->where('slug', $slug)->firstOrFail();

        if ($webinar->status !== 'upcoming') {
            return response()->json(['message' => 'Webinar này đã kết thúc hoặc bị hủy.'], 422);
        }

        if ($webinar->max_attendees) {
            $currentCount = $webinar->registrations()->where('status', '!=', 'cancelled')->count();
            if ($currentCount >= $webinar->max_attendees) {
                return response()->json(['message' => 'Webinar đã đủ số lượng đăng ký.'], 422);
            }
        }

        $email = $request->input('email');

        // Find or create user by email
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make(\Illuminate\Support\Str::random(16)),
            ]);
        }

        // Check existing registration
        $existing = WebinarRegistration::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'cancelled') {
                $existing->update(['status' => 'registered']);
            } else {
                // Already registered - still send email in case they lost it
                try {
                    MailConfigService::applyFromSettings();
                    Mail::to($user->email)->send(new WebinarRegistrationMail($webinar, $user));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send webinar registration email: ' . $e->getMessage());
                }
                return response()->json(['message' => 'Đăng ký thành công! Link webinar đã được gửi qua email.']);
            }
        } else {
            WebinarRegistration::create([
                'webinar_id' => $webinar->id,
                'user_id' => $user->id,
                'status' => 'registered',
            ]);
        }

        // Send webinar link email
        try {
            MailConfigService::applyFromSettings();
            Mail::to($user->email)->send(new WebinarRegistrationMail($webinar, $user));
        } catch (\Throwable $e) {
            Log::warning('Failed to send webinar registration email: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Đăng ký thành công! Link webinar đã được gửi qua email.'], 201);
    }

    public function cancelRegistration(Request $request, string $slug)
    {
        $webinar = Webinar::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $registration = WebinarRegistration::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->firstOrFail();

        $registration->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Đã hủy đăng ký webinar.']);
    }
}
