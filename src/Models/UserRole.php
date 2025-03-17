<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    protected $table = 'user_roles';

    public function user()
    {
        return $this->belongsTo(config('abac.user_model'));
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}