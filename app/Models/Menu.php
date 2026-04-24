<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'target',
        'icon',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'parent_id' => 'integer',
    ];

    /* ── Relationships ── */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    /* ── Scopes ── */

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /* ── Helper to build full nested tree ── */

    public static function tree(bool $activeOnly = false)
    {
        $query = static::topLevel()->ordered();
        if ($activeOnly) {
            $query->active()->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('position');
            }]);
        } else {
            $query->with(['children' => function ($q) {
                $q->orderBy('position');
            }]);
        }
        return $query->get();
    }
}
