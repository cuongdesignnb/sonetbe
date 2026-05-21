<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_group_id',
        'user_id',
    ];

    // Relationships

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
