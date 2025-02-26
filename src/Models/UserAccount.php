<?php
namespace joey\abac\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Pivot
{
    protected $table = 'user_accounts';

    public function user()
    {
        return $this->belongsTo(config('abac.user_model'));
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}