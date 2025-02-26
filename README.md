# Laravel ABAC - Attribute-Based Access Control Package

A robust ABAC implementation for Laravel providing attribute-based permission management with caching and automatic permission recaching.

## Features
- Attribute-based access control (ABAC)
- Permission caching for high performance
- Multiple permission types (on-off, read-write, crud)
- Account-based resource blocking
- Automatic permission recaching on changes
- Middleware and Policy integration
- Role-based inheritance with user overrides

## Requirements
- PHP 8.1+
- Laravel 10+
- Database supporting JSON columns

## Installation

Install via Composer:
```bash
composer require joey/abac
```

Run the install command:
```bash
php artisan abac:install
```

## Configuration

Publish the config file (optional):
```bash
php artisan vendor:publish --tag=abac-config
```

`config/abac.php`:
```php
return [
  'user_model' => App\Models\User::class, // Your user model
  'cache_ttl' => 86400, // Permission cache duration in seconds
  'account_status_check' => true, // Enable account active status check
];
```

## Model Setup

Add the `HasPermissions` trait to your User model:

```php
// use App\Traits\HasPermissions;
use joey\abac\Traits\HasPermissions;

class User extends Authenticatable
{
  use HasPermissions;
  // ...
}
```

This trait provides permission management functionality including:
- Permission checking methods
- Role relationship definitions
- Permission caching logic
- Account status handling


## Usage

### Middleware

Protect routes with ABAC middleware:
```php
// Require 'read' access on 'reports' permission
Route::get('/reports', [ReportController::class, 'index'])
  ->middleware('abac:reports,read');
```

### Gates & Policies

Check permissions in controllers/policies:
```php
public function viewDashboard(User $user)
{
  return Gate::check('abac', ['dashboard', 'read']);
}
```

### Manual Checks
```php
if (auth()->user()->can('abac', ['settings', 'write'])) {
  // Update settings
}
```

## Permission Types

### On/Off (on-off):
```php
// Returns boolean
Gate::check('abac', ['notifications', 'on']);
```

### Read/Write (read-write):
```php
// Must specify required access type
Gate::check('abac', ['documents', 'write']);
```

### CRUD (crud):
```php
// Check specific CRUD operation
Gate::check('abac', ['posts', 'delete']);
```

## Recaching Permissions

When permissions change:
```php
$user->recachePermissions(); // Recache individual user
```

## Account Status Handling

Accounts marked as inactive (`is_active = false`) automatically receive:
- 403 Forbidden for all ABAC-protected routes

## Permission Assignment Example
```php
// Create permission
$permission = Permission::create([
  'slug' => 'manage-payments',
  'name' => 'Payment Management',
  'type' => 'read-write',
]);

// Assign to user
AssignedPermission::create([
  'permission_id' => $permission->id,
  'assignee_id' => $user->id,
  'assignee_type' => 'user',
  'access' => ['write']
]);

// Assign to role
AssignedPermission::create([
  'permission_id' => $permission->id,
  'assignee_id' => $role->id,
  'assignee_type' => 'role',
  'access' => ['read']
]);
```

## Models & Relationships

Key models included:
- PermissionCategory → Permission
- Role ↔ User (via UserRole)
- Account ↔ AssignedPermission

## Automatic Recaching

Permissions are automatically recached when:
- User roles change
- Permissions are added/removed
- Account associations change

## Additional Examples

### Get User's Permissions Through Roles
```php
// Fetch all permissions assigned to the user's roles
$user->roles()->with('permissions')->get();
```
This snippet retrieves all the permissions associated with the roles assigned to a user. It uses Eloquent's `with` method to eager load the `permissions` relationship on the `roles` model.

### Get All Assigned Permissions for an Account
```php
// Fetch all permissions assigned to an account
$account->assignedPermissions()->with('permission')->get();
```
This snippet retrieves all the permissions assigned to a specific account. It uses Eloquent's `with` method to eager load the `permission` relationship on the `assignedPermissions` model.

### Check Permission Assignments for a Resource
```php
// Check if a specific permission is assigned to an account
$permission->assignedPermissions()->where('account_id', $accountId)->get();
```
This snippet checks if a specific permission is assigned to an account by filtering the `assignedPermissions` relationship based on the `account_id`.

### Auto-Assign Default Role & Account
```php
// In User model
protected static function boot()
{
  parent::boot();

  static::created(function ($user) {
    // Assign to default user account
    $userAccount = Account::where('type', 'user')->first();
    $userRole = Role::where('name', 'Customer')->first();

    $user->update(['account_id' => $userAccount->id]);
    $user->roles()->attach($userRole);
  });
}
```
This snippet automatically assigns new users to the default 'user' account type and 'Customer' role when they are created.


### Create Admin User Example
```php
// Manually create admin user
$adminAccount = Account::where('type', 'admin')->first();
$adminRole = Role::where('name', 'Administrator')->first();

$user = User::create([...]);
$user->account()->associate($adminAccount);
$user->roles()->attach($adminRole);
```

### Controller Authorization Example
```php
// In controller
public function editProduct(Product $product)
{
  $this->authorize('abac', ['manage-products', 'update']);
  // ...
}
```


## License

MIT License - See LICENSE for details.