<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class AbacDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Default Account
        $accountId = 1;
        DB::table('accounts')->insert(

            [
            'id' => $accountId,
            'name' => 'Default Account',
            'type' => 'admin',
            'is_active' => true,
        ]);

        // Default Role
        $roleId = 1;
        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => 'SuperUser',
            'description' => 'Administrator role with full access',
        ]);

        // Permission Categories
        $categories = [
            ['id' => 1, 'name' => 'Dashboard', 'description' => 'Access to dashboard features'],
            ['id' => 2, 'name' => 'Accounts', 'description' => 'Manage user accounts'],
            ['id' => 3, 'name' => 'Products', 'description' => 'Manage products and inventory'],
            ['id' => 4, 'name' => 'Likes', 'description' => 'Manage likes and interactions'],
        ];
        DB::table('permission_categories')->insert($categories);

        // Permissions
        $permissions = [
            ['id' => 1, 'category_id' => 1, 'slug' => 'dashboard-access', 'name' => 'Dashboard Access', 'type' => 'on-off'],
            ['id' => 2, 'category_id' => 2, 'slug' => 'account-manage', 'name' => 'Manage Accounts', 'type' => 'crud'],
            ['id' => 3, 'category_id' => 3, 'slug' => 'product-manage', 'name' => 'Manage Products', 'type' => 'crud'],
            ['id' => 4, 'category_id' => 4, 'slug' => 'like-interact', 'name' => 'Like & Interact', 'type' => 'read-write'],
        ];
        DB::table('permissions')->insert($permissions);


        $adminUser = config('abac.user_model')::create([
            'name' => 'System Admin',
            'email' => 'admin@abac.test',
            'password' => bcrypt('password'),
            'phone' => '2128081980',
        ]);


        // Assign Default Role to User
        $userId = 1; // Ensure this user exists
        DB::table('user_roles')->insert([
            'user_id' =>$userId,
            'role_id' => $roleId,
        ]);
        $userId = 1; // Ensure this user exists
        DB::table('user_accounts')->insert([
            'user_id' =>$accountId,
            'account_id' => $roleId,
            'is_active' => true
        ]);
        // Assign Permissions to Role
        $assignedPermissions = [
            [
                'id' => 1,
                'account_id' => $accountId,
                'permission_id' => 1,
                'assignee_id' => $roleId,
                'assignee_type' => 'role',
                'access' => 'on-off',
            ],
            [
                'id' => 2,
                'account_id' => $accountId,
                'permission_id' => 2,
                'assignee_id' => $roleId,
                'assignee_type' => 'role',
                'access' => 'crud',
            ],
            [
                'id' => 3,
                'account_id' => $accountId,
                'permission_id' => 3,
                'assignee_id' => $roleId,
                'assignee_type' => 'role',
                'access' => 'crud',
            ],
            [
                'id' => 4,
                'account_id' => $accountId,
                'permission_id' => 4,
                'assignee_id' => $roleId,
                'assignee_type' => 'role',
                'access' => 'read-write',
            ],


        ];
        DB::table('assigned_permissions')->insert($assignedPermissions);


    }


    /**
     * Return the default access for a given permission type.
     *
     * @param string $type The permission type.
     *
     * @return array|bool The default access for the given type.
     */
    private function getAccess($type)
    {
        return match($type) {
            'crud' => ['create', 'read', 'update', 'delete'],
            'read-write' => ['read', 'write'],
            'on-off' => true
        };
    }

}
