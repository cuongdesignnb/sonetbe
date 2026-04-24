<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'average_rating',
        'total_enrollments',
        'total_duration',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'original_price',
        'thumbnail',
        'preview_video',
        'category_id',
        'instructor_id',
        'level',
        'duration',
        'status',
        'sort_order',
        'badge_text',
        'badge_color',
        'meta_description',
        'tags',
        'marketing'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'tags' => 'array',
        'marketing' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Resolve a course by numeric ID or slug string.
     */
    public static function findByIdOrSlug($identifier)
    {
        if (is_numeric($identifier)) {
            return static::find($identifier);
        }
        return static::where('slug', $identifier)->first();
    }

    /**
     * Resolve a course by numeric ID or slug string, or fail with 404.
     */
    public static function findByIdOrSlugOrFail($identifier)
    {
        if (is_numeric($identifier)) {
            return static::findOrFail($identifier);
        }
        return static::where('slug', $identifier)->firstOrFail();
    }

    // Relationships
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function faqs()
    {
        return $this->hasMany(CourseFaq::class)->orderBy('order')->orderBy('id');
    }

    // Accessors
    public function getAverageRatingAttribute()
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return $this->attributes['reviews_avg_rating'];
        }

        return $this->reviews()->where('is_approved', true)->avg('rating');
    }

    public function getTotalEnrollmentsAttribute()
    {
        if (array_key_exists('enrollments_count', $this->attributes)) {
            return $this->attributes['enrollments_count'];
        }

        return $this->enrollments()->count();
    }

    public function getTotalDurationAttribute()
    {
        if (array_key_exists('lessons_sum_duration', $this->attributes)) {
            return $this->attributes['lessons_sum_duration'] ?? 0;
        }

        if ($this->relationLoaded('lessons')) {
            return $this->lessons->sum('duration');
        }

        return $this->lessons()->sum('duration');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeVisible($query)
    {
        return $query->whereIn('status', ['published', 'coming_soon']);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}
