<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'user_id',
        'order',
    ];

    public function parent()
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('order');
    }

    public function assets()
    {
        return $this->hasMany(MediaAsset::class, 'folder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
