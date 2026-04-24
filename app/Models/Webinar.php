<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Webinar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail',
        'instructor_name',
        'instructor_avatar',
        'zoom_link',
        'replay_url',
        'replay_bunny_id',
        'replay_bunny_library_id',
        'scheduled_at',
        'duration_minutes',
        'views_count',
        'status',
        'is_free',
        'price',
        'tags',
        'benefits',
        'speakers',
        'max_attendees',
        'fake_registrations',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'tags' => 'array',
        'benefits' => 'array',
        'speakers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Webinar $webinar) {
            if (empty($webinar->slug)) {
                $webinar->slug = Str::slug($webinar->title) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function registrations()
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->where('scheduled_at', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePublic($query)
    {
        return $query->whereIn('status', ['upcoming', 'live', 'completed']);
    }
}
