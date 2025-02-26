<?php

namespace joey\abac\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(Account::class);
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function assignedPermissions()
    {
        return $this->hasMany(AssignedPermission::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_accounts')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function scopePrimaryForUser($query, $userId)
    {
        return $query->whereHas('users', function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('is_primary', true);
        });
    }
}