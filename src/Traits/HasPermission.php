<?php

namespace awesome166\abac\Traits;

use App\Models\UserAccount;
use Illuminate\Support\Facades\Cache;
use awesome166\abac\Models\AssignedPermission;
use awesome166\abac\Models\Account;
use awesome166\abac\Models\Role;
use awesome166\abac\Models\UserRole;

// app/Traits/HasPermissions.php
trait HasPermission
{
    /**
     * Get the account that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */   public function accounts()
    {
        return $this->belongsToMany(Account::class, 'user_accounts')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryAccount()
    {
        return $this->belongsTo(Account::class);
    }

    public function attachToAccount($accountId, $isPrimary = false)
    {
        $this->accounts()->syncWithoutDetaching([
            $accountId => ['is_primary' => $isPrimary]
        ]);

        if ($isPrimary) {
            $this->update(['account_id' => $accountId]);
        }

        $this->recachePermissions();
        return $this;
    }

    public function detachFromAccount($accountId)
    {
        $this->accounts()->detach($accountId);

        if ($this->account_id == $accountId) {
            $this->update(['account_id' => null]);
        }

        $this->recachePermissions();
        return $this;
    }

    public function setPrimaryAccount($accountId)
    {
        $this->accounts()->updateExistingPivot($accountId, [
            'is_primary' => true
        ]);

        $this->update(['account_id' => $accountId]);
        return $this;
    }

    public function getAllAccountsAttribute()
    {
        return $this->accounts->merge(
            $this->primaryAccount ? [$this->primaryAccount] : []
        )->unique('id');
    }
    /**
     * Get the roles that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */

    public function roles()
    {
        return $this->belongsToMany(Role::class, UserRole::class);
    }

    /**
     * The permissions that are assigned to the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function assignedPermissions()
    {
        return $this->morphMany(AssignedPermission::class, 'assignee');
    }

    public function getPermissionsAttribute()
    {
        return Cache::remember("user_permissions:{$this->id}", now()->addDay(), function () {
            return $this->calculatePermissions();
        });
    }

    private function calculatePermissions()
    {
        return Cache::remember("user_permissions:{$this->id}", now()->addDay(), function () {
            $accountIds = $this->all_accounts->pluck('id');

            $permissions = AssignedPermission::where(function ($query) use ($accountIds) {
                $query->whereIn('account_id', $accountIds)
                      ->orWhereNull('account_id');
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('assignee_type', 'user')
                      ->where('assignee_id', $this->id);
                })
                ->orWhere(function ($q) {
                    $q->where('assignee_type', 'role')
                      ->whereIn('assignee_id', $this->roles->pluck('id'));
                });
            })
            ->with('permission')
            ->get();

            $processed = [];
            foreach ($permissions as $assigned) {
                $slug = $assigned->permission->slug;
                $type = $assigned->permission->type;
                $access = $assigned->access;

                if (!isset($processed[$slug])) {
                    $processed[$slug] = [
                        'type' => $type,
                        'access' => $access,
                        'sources' => [$assigned->account_id ?: 'global']
                    ];
                } else {
                    // User-specific permissions override role permissions
                    if ($assigned->assignee_type === 'user') {
                        $processed[$slug] = [
                            'type' => $type,
                            'access' => $access,
                            'sources' => array_unique([...$processed[$slug]['sources'], $assigned->account_id ?: 'global'])
                        ];
                    } else {
                        $processed[$slug]['sources'] = array_unique([
                            ...$processed[$slug]['sources'],
                            $assigned->account_id ?: 'global'
                        ]);
                    }
                }
            }

            return $processed;
        });
    }

    public function recachePermissions()
    {
        Cache::forget("user_permissions:{$this->id}");
        $this->getPermissionsAttribute();
    }
}
