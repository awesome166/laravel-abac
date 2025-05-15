<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AbacDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Default Account
        $accountId = 1;
        DB::table('accounts')->updateOrInsert(
            ['id' => $accountId],
            [
                'name' => 'Default Account',
                'type' => 'admin',
                'is_active' => true,
            ]
        );

        // Create Roles
        $roles = [
            ['id' => 1, 'name' => 'Super', 'description' => 'Super roles', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Customer', 'description' => 'User roles', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Agent', 'description' => 'Agent role', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('roles')->upsert($roles, ['id'], ['name', 'description', 'updated_at']);

        // Permission Categories
        $categories = [
            ['id' => 1, 'name' => 'Dashboard', 'description' => 'Access to dashboard features'],
            ['id' => 2, 'name' => 'Accounts', 'description' => 'Manage user accounts'],
            ['id' => 3, 'name' => 'Products', 'description' => 'Manage products and inventory'],
            ['id' => 4, 'name' => 'Product Categories', 'description' => 'Manage categories'],
            ['id' => 5, 'name' => 'Roles', 'description' => 'Manage roles'],
            ['id' => 6, 'name' => 'Permissions', 'description' => 'Manage permissions'],
            ['id' => 7, 'name' => 'Customers', 'description' => 'Manage Customers'],
        ];
        DB::table('permission_categories')->upsert($categories, ['id'], ['name', 'description']);

        // Permissions
        $permissions = [
            ['id' => 1, 'category_id' => 1, 'slug' => 'dashboard-access', 'name' => 'Dashboard Access', 'type' => 'on-off', 'account_type' => json_encode(['role', 'user'])],
            ['id' => 2, 'category_id' => 2, 'slug' => 'manage-accounts', 'name' => 'Manage Accounts', 'type' => 'crud', 'account_type' => json_encode(['role'])],
            ['id' => 3, 'category_id' => 3, 'slug' => 'manage-products', 'name' => 'Manage Products', 'type' => 'crud', 'account_type' => json_encode(['role'])],
            ['id' => 4, 'category_id' => 4, 'slug' => 'product-category', 'name' => 'Product Category', 'type' => 'on-off', 'account_type' => json_encode(['role', 'user'])],
            ['id' => 5, 'category_id' => 4, 'slug' => 'manage-product-category', 'name' => 'Manage Product Category', 'type' => 'crud', 'account_type' => json_encode(['role', 'user'])],
        ];
        DB::table('permissions')->upsert($permissions, ['id'], ['name', 'slug', 'type', 'account_type', 'category_id']);

        // Create Admin User
        $user = config('abac.user_model')::firstOrCreate(
            ['email' => 'joeymantey@gmail.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('acidremantey'),
                'phone' => '2128081980',
            ]
        );

        $userId = $user->id;

        // Assign Role to User
        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $userId, 'role_id' => 1],
            ['user_id' => $userId, 'role_id' => 1]
        );

        // Assign User to Account
        DB::table('user_accounts')->updateOrInsert(
            ['user_id' => $userId, 'account_id' => $accountId],
            ['is_active' => true]
        );

        // Assign Permissions to Role
        $permissionsToAssign = [
            ['permission_id' => 1, 'type' => 'on-off'],
            ['permission_id' => 2, 'type' => 'crud'],
            ['permission_id' => 3, 'type' => 'crud'],
            ['permission_id' => 4, 'type' => 'on-off'],
            ['permission_id' => 5, 'type' => 'crud'],
        ];

        foreach ($permissionsToAssign as $item) {
            DB::table('assigned_permissions')->updateOrInsert(
                [
                    'account_id' => $accountId,
                    'permission_id' => $item['permission_id'],
                    'assignee_id' => 1,
                    'assignee_type' => 'role',
                ],
                [
                    'access' => json_encode($this->getAccess($item['type']))
                ]
            );
        }
    }

    /**
     * Get access values by permission type
     */
    private function getAccess(string $type): array
    {
        return match ($type) {
            'crud' => ['create', 'read', 'update', 'delete'],
            'read-write' => ['read', 'write'],
            'on-off' => ['on'],
            default => [],
        };
    }
}
