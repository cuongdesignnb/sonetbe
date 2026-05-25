<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CoursePayment;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'section_id',
        'duration_tier_id',
        'status',
        'enrolled_at',
        'completed_at',
        'expires_at',
        'payment_id',
        'amount_paid'
    ];

    protected $casts = [
        'section_id' => 'integer',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(CoursePayment::class)->latestOfMany('id');
    }

    public function durationTier()
    {
        return $this->belongsTo(CourseDurationTier::class, 'duration_tier_id');
    }

    // Helpers

    /**
     * Check if this enrollment has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if this enrollment has lifetime (no expiry) access.
     */
    public function isLifetime(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * Get the number of days remaining. Returns null for lifetime.
     */
    public function daysRemaining(): ?int
    {
        if ($this->isLifetime()) {
            return null;
        }
        if ($this->isExpired()) {
            return 0;
        }
        return (int) now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if enrollment is still valid (active and not expired).
     */
    public function isAccessible(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAccessible($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}