<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CoursePayment;
use Carbon\Carbon;

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

    public function payments()
    {
        return $this->hasMany(CoursePayment::class);
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
        // Round up: 23 hours left is "1 day left", not "0 days".
        return (int) ceil(now()->diffInSeconds($this->expires_at, false) / 86400);
    }

    /**
     * Expiry after buying (or being granted) a package of $durationDays days.
     * null days means lifetime. Time is added on top of the remaining access
     * when the enrollment is still valid, so renewing early never loses days;
     * an expired or pending enrollment starts from now. Lifetime access is
     * never downgraded to a time-limited one.
     */
    public function accessExpiryAfterPurchase(?int $durationDays): ?Carbon
    {
        if ($durationDays === null) {
            return null;
        }

        if ($this->isAccessible() && $this->isLifetime()) {
            return null;
        }

        $base = $this->isAccessible() && $this->expires_at
            ? $this->expires_at->copy()
            : now();

        return $base->addDays($durationDays);
    }

    /**
     * Access state for admin/student displays:
     * pending | lifetime | active | expiring (<= 7 days) | expired | inactive
     */
    public function accessState(): string
    {
        if ($this->status === 'pending') {
            return 'pending';
        }
        if ($this->status !== 'active') {
            return 'inactive';
        }
        if ($this->isLifetime()) {
            return 'lifetime';
        }
        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->daysRemaining() <= 7 ? 'expiring' : 'active';
    }

    /**
     * Human-readable description of what was bought, e.g.
     * "Toàn khoá · 3 tháng" or "Chương lẻ: Chương 2 - ...".
     */
    public function purchaseLabel(): string
    {
        if ($this->section_id) {
            $title = $this->section?->title;
            return 'Chương lẻ' . ($title ? ': ' . $title : ' #' . $this->section_id);
        }

        $tier = $this->durationTier;
        if ($tier) {
            return 'Toàn khoá · ' . $tier->label;
        }

        return 'Toàn khoá' . ($this->isLifetime() ? ' · Vĩnh viễn' : '');
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
        return $query->whereNotNull('completed_at');
    }
}