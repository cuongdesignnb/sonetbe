<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\SettingsService;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'thumbnail',
        'video_url',
        'embed_url',
        'video_bunny_id',
        'video_bunny_library_id',
        'video_local_path',
        'duration',
        'order',
        'is_preview',
        'content',
        'resources'
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'resources' => 'array',
        'duration' => 'integer'
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Check if lesson is completed by a user
    public function isCompletedBy($userId)
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('completed', true)
            ->exists();
    }

    // Get video URL based on storage type
    public function getVideoUrlAttribute($value)
    {
        if ($this->video_bunny_id) {
            return null;
        }
        
        return $this->video_local_path ? asset('storage/' . $this->video_local_path) : null;
    }
}