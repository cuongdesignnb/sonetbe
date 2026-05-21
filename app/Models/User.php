<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'phone',
        'cccd',
        'date_of_birth',
        'is_active',
        'referral_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function instructorCourses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function groups()
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_members')
            ->withTimestamps();
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Check if user is enrolled in a course
    public function isEnrolledIn($courseId)
    {
        return $this->enrollments()
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    // Get user's course progress
    public function getCourseProgress($courseId)
    {
        $course = Course::with('lessons')->find($courseId);
        if (!$course) return 0;

        $totalLessons = $course->lessons->count();
        if ($totalLessons === 0) return 0;

        $completedLessons = $this->lessonProgress()
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->where('completed', true)
            ->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    // Check if user is instructor
    public function isInstructor()
    {
        return $this->role === 'instructor';
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}