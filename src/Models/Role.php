<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(config('abac.user_model'))
            ->using(UserRole::class);
    }

    public function assignedPermissions()
    {
        return $this->morphMany(AssignedPermission::class, 'assignee');
    }
}
