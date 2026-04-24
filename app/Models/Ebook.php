<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ebook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail',
        'author_name',
        'file_url',
        'preview_url',
        'price',
        'original_price',
        'total_pages',
        'download_count',
        'type',
        'status',
        'features',
        'tags',
        'category_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'features' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ebook $ebook) {
            if (empty($ebook->slug)) {
                $ebook->slug = Str::slug($ebook->title) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeComingSoon($query)
    {
        return $query->where('status', 'coming_soon');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['published', 'coming_soon']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
