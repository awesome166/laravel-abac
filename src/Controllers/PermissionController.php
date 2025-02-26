<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Permission,
    Role,
    AssignedPermission,
    PermissionCategory,
    Account
};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PermissionsController extends Controller
{
    /**
     * Get all permissions with categories
     */
    public function index()
    {
        return response()->json([
            'permissions' => Permission::with('category')->get(),
            'categories' => PermissionCategory::all()
        ]);
    }

    /**
     * Create new permission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|unique:permissions|max:30',
            'name' => 'required|max:60',
            'type' => ['required', Rule::in(['on-off', 'read-write', 'crud'])],
            'category_id' => 'required|exists:permission_categories,id',
            'description' => 'nullable|max:120',
            'account_type' => 'nullable|json'
        ]);

        $permission = Permission::create($validated);
        return response()->json($permission, 201);
    }

    /**
     * Update existing permission
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'slug' => ['required', 'max:30', Rule::unique('permissions')->ignore($permission->id)],
            'name' => 'required|max:60',
            'type' => ['required', Rule::in(['on-off', 'read-write', 'crud'])],
            'category_id' => 'required|exists:permission_categories,id',
            'description' => 'nullable|max:120',
            'account_type' => 'nullable|json'
        ]);

        $permission->update($validated);
        return response()->json($permission);
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(null, 204);
    }

    /**
     * Assign permission to user/role
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'assignee_id' => 'required',
            'assignee_type' => ['required', Rule::in(['user', 'role', 'token'])],
            'account_id' => 'nullable|exists:accounts,id',
            'access' => 'required'
        ]);

        // Validate access based on permission type
        $permission = Permission::findOrFail($validated['permission_id']);
        $this->validateAccess($permission->type, $validated['access']);

        // Check existing assignment
        $existing = AssignedPermission::where([
            'permission_id' => $validated['permission_id'],
            'assignee_id' => $validated['assignee_id'],
            'assignee_type' => $validated['assignee_type']
        ])->first();

        if ($existing) {
            return response()->json(['message' => 'Assignment already exists'], 409);
        }

        $assignment = AssignedPermission::create([
            // 'id' => null,
            ...$validated,
            'access' => $this->formatAccess($permission->type, $validated['access'])
        ]);

        $this->recacheAffected($assignment);
        return response()->json($assignment, 201);
    }

    /**
     * Update existing permission assignment
     */
    public function updateAssignment(Request $request, AssignedPermission $assignment)
    {
        $validated = $request->validate([
            'access' => 'required',
            'account_id' => 'nullable|exists:accounts,id'
        ]);

        $permission = $assignment->permission;
        $this->validateAccess($permission->type, $validated['access']);

        $assignment->update([
            ...$validated,
            'access' => $this->formatAccess($permission->type, $validated['access'])
        ]);

        $this->recacheAffected($assignment);
        return response()->json($assignment);
    }

    /**
     * Remove permission assignment
     */
    public function removeAssignment(AssignedPermission $assignment)
    {
        $assignment->delete();
        $this->recacheAffected($assignment);
        return response()->json(null, 204);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId)
    {
        $user = config('abac.user_model')::with(['roles', 'assignedPermissions'])->findOrFail($userId);

        return response()->json([
            'direct' => $user->assignedPermissions,
            'via_roles' => $user->roles()->with('assignedPermissions')->get(),
            'effective' => $user->permissions
        ]);
    }

    /**
     * List all roles
     */
    public function roles()
    {
        return response()->json(Role::with('assignedPermissions')->get());
    }

    /**
     * Create new role
     */
    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles|max:60',
            'description' => 'nullable|max:120'
        ]);

        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, $userId)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = config('abac.user_model')::findOrFail($userId);
        $user->roles()->syncWithoutDetaching($validated['role_id']);
        $user->recachePermissions();

        return response()->json($user->roles);
    }

    /**
     * Sync account permissions
     */
    public function syncAccountPermissions(Request $request, $accountId)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.access' => 'required'
        ]);

        $account = Account::findOrFail($accountId);

        foreach ($validated['permissions'] as $perm) {
            $permission = Permission::find($perm['permission_id']);
            $this->validateAccess($permission->type, $perm['access']);

            AssignedPermission::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'permission_id' => $perm['permission_id']
                ],
                [
                    'access' => $this->formatAccess($permission->type, $perm['access'])
                ]
            );
        }

        return response()->json($account->assignedPermissions);
    }

    /**
     * Check access for user
     */
    public function canAccess(Request $request, $userId)
    {
        $request->validate([
            'permission' => 'required',
            'access' => 'required_if:type,read-write,crud'
        ]);

        $user = config('abac.user_model')::findOrFail($userId);

        return response()->json([
            'result' => Gate::forUser($user)->check(
                'abac',
                $request->only(['permission', 'access'])
            )
        ]);
    }

    /**
     * Validate access format based on permission type
     */
    private function validateAccess($type, $access)
    {
        $validator = Validator::make(['access' => $access], [
            'access' => match($type) {
                'on-off' => 'boolean',
                'read-write' => ['array', Rule::in(['read', 'write'])],
                'crud' => ['array', Rule::in(['create', 'read', 'update', 'delete'])]
            }
        ]);

        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }
    }

    /**
     * Format access for storage
     */
    private function formatAccess($type, $access)
    {
        return match($type) {
            'on-off' => (bool)$access,
            default => (array)$access
        };
    }

    /**
     * Recache affected users when assignments change
     */
    private function recacheAffected(AssignedPermission $assignment)
    {
        if ($assignment->assignee_type === 'user') {
            $assignment->assignee->recachePermissions();
        } elseif ($assignment->assignee_type === 'role') {
            Role::find($assignment->assignee_id)
                ->users()
                ->each(fn($user) => $user->recachePermissions());
        }
    }
}