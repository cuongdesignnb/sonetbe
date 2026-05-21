<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseDurationTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'label',
        'duration_days',
        'price',
        'original_price',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'duration_tier_id');
    }

    public function targets()
    {
        return $this->hasMany(CourseDurationTierTarget::class, 'duration_tier_id');
    }

    /**
     * Check if this tier has targeting restrictions.
     */
    public function isTargeted(): bool
    {
        return $this->targets()->exists();
    }

    /**
     * Check if a specific user is allowed to purchase this tier.
     * If tier has no targets, it's available to everyone.
     * If tier has targets, user must be directly targeted or belong to a targeted group.
     */
    public function isAvailableForUser($userId): bool
    {
        // No targets = available to everyone
        if (!$this->isTargeted()) {
            return true;
        }

        // Check direct user targeting
        $directTarget = $this->targets()
            ->where('target_type', 'user')
            ->where('target_id', $userId)
            ->exists();

        if ($directTarget) {
            return true;
        }

        // Check group targeting
        $targetGroupIds = $this->targets()
            ->where('target_type', 'group')
            ->pluck('target_id');

        if ($targetGroupIds->isEmpty()) {
            return false;
        }

        return UserGroupMember::where('user_id', $userId)
            ->whereIn('user_group_id', $targetGroupIds)
            ->exists();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // Helpers
    public function isLifetime(): bool
    {
        return $this->duration_days === null;
    }

    /**
     * Get human-readable duration label.
     */
    public function getDurationLabelAttribute(): string
    {
        if ($this->isLifetime()) {
            return 'Vĩnh viễn';
        }

        if ($this->duration_days >= 365) {
            $years = round($this->duration_days / 365, 1);
            return $years == 1 ? '1 năm' : "{$years} năm";
        }

        if ($this->duration_days >= 30) {
            $months = round($this->duration_days / 30);
            return "{$months} tháng";
        }

        return "{$this->duration_days} ngày";
    }
}
