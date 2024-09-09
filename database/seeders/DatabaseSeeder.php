<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        // Default permissions
        $permissions = [
            'user_view' => 'admin',
            'user_add' => 'admin',
            'user_update' => 'admin',
            'user_delete' => 'admin',
            'category_view' => 'admin',
            'category_add' => 'admin',
            'category_update' => 'admin',
            'category_delete' => 'admin',
            'product_view' => 'admin',
            'product_add' => 'admin',
            'product_update' => 'admin',
            'product_delete' => 'admin',
            'toping_view' => 'admin',
            'toping_add' => 'admin',
            'toping_update' => 'admin',
            'toping_delete' => 'admin',
            'settings_view' => 'admin',
            'settings_update' => 'admin',
            'table_area_view' => 'admin',
            'table_area_add' => 'admin',
            'table_area_update' => 'admin',
            'table_area_delete' => 'admin',
            'branch_view' => 'admin',
            'branch_add' => 'admin',
            'branch_update' => 'admin',
            'branch_sync' => 'admin',
            'branch_delete' => 'admin',
            'taxes_view' => 'admin',
            'taxes_add' => 'admin',
            'taxes_update' => 'admin',
            'taxes_delete' => 'admin',
            'query_view' => 'admin',
            'query_add' => 'admin',
            'query_update' => 'admin',
            'query_delete' => 'admin',
            'language_view' => 'admin',
            'language_add' => 'admin',
            'language_update' => 'admin',
            'category_view' => 'admin',
            'category_add' => 'admin',
            'category_update' => 'admin',
            'category_delete' => 'admin',
            'customer_devices_view' => 'admin',
            'customer_devices_add' => 'admin',
            'customer_devices_update' => 'admin',
            'customer_devices_delete' => 'admin',
            'activity_log_view' => 'admin',
        ];

        // Set default roles to database
        $roles = [
            "admin",
            "staff",
        ];

        foreach ($roles as $role) {
            $ro = DB::table("roles")->select("id")->where("role", $role)->first();
            if (!$ro) {
                DB::table('roles')->insert([
                    'role' => $role,
                    'status' => 1
                ]);
            }
        }

        $rolesA = DB::table("roles")->select("id", "role")->get();

        $admin_permissions = [
            'user_view',
            'user_add',
            'user_update',
            'user_delete',
            'category_view',
            'category_add',
            'category_update',
            'category_delete',
            'product_view',
            'product_add',
            'product_update',
            'product_delete',
            'toping_view',
            'toping_add',
            'toping_update',
            'toping_delete',
            'settings_view',
            'settings_update',
            'table_area_view',
            'table_area_add',
            'table_area_update',
            'table_area_delete',
            'branch_view',
            'branch_add',
            'branch_update',
            'branch_sync',
            'branch_delete',
            'taxes_view',
            'taxes_add',
            'taxes_update',
            'taxes_delete',
            'query_view',
            'query_add',
            'query_update',
            'query_delete',
            'language_view',
            'language_add',
            'language_update',
            'category_view',
            'category_add',
            'category_update',
            'category_delete',
            'customer_devices_view',
            'customer_devices_add',
            'customer_devices_update',
            'customer_devices_delete',
            'activity_log_view',
        ];

        $staff_permissions = [
            'user_view',
            'user_add',
            'user_update',
            'category_view',
            'product_view',
            'toping_view',
            'settings_view',
            'settings_update',
        ];

        foreach ($rolesA as $role) {
            $role_permissions = [];
            if ($role->role == "admin") {
                $role_permissions = $admin_permissions;
            } elseif ($role->role == "staff") {
                $role_permissions = $staff_permissions;
            }
            foreach ($role_permissions as $permission) {
                $perm = DB::table("permissions")->select("id")->where("permission", $permission)->first();
                if (!$perm) {
                    DB::table('permissions')->insert([
                        'permission' => $permission
                    ]);
                    $perm = DB::table("permissions")->select("id")->where("permission", $permission)->first();
                }
                $added = DB::table("permissions_in_roles")->select("id")->where("role_id", $role->id)->where("permission_id", $perm->id)->first();
                if (!$added) {
                    DB::table('permissions_in_roles')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $perm->id
                    ]);
                }
            }
        }

        // Create default branch
        $new_branch = DB::table("branches")->where("id", 1)->first();
        if (!$new_branch) {
            DB::table('branches')->insert([
                'id' => 1,
                'name' => 'Branch 1',
            ]);
        }

        // Create default user
        $new_user = DB::table("users")->where("email", "admin@abc.com")->first();
        if (!$new_user) {
            DB::table('users')->insert([
                'branch_id' => 1,
                'name' => 'admin',
                'email' => 'admin@abc.com',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'role_id' => 1,
                'branches' => json_encode([]),
                'status' => 1
            ]);
        }
    }
}
