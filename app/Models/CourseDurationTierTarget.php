<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseDurationTierTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'duration_tier_id',
        'target_type',
        'target_id',
    ];

    // Relationships

    public function durationTier()
    {
        return $this->belongsTo(CourseDurationTier::class, 'duration_tier_id');
    }

    /**
     * Get the target entity (User or UserGroup).
     */
    public function target()
    {
        if ($this->target_type === 'user') {
            return $this->belongsTo(User::class, 'target_id');
        }
        return $this->belongsTo(UserGroup::class, 'target_id');
    }

    // Scopes

    public function scopeForUser($query, $userId)
    {
        return $query->where('target_type', 'user')->where('target_id', $userId);
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('target_type', 'group')->where('target_id', $groupId);
    }
}
