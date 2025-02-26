<?php
namespace joey\abac\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(PermissionCategory::class);
    }

    public function assignedPermissions()
    {
        return $this->hasMany(AssignedPermission::class);
    }

}