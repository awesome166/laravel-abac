<?php

namespace joey\abac\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedPermission extends Model
{
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function assignee()
    {
        return $this->morphTo();
    }
}