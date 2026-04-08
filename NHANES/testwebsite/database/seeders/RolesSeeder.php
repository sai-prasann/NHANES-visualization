<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standard = Role::create(['name' => 'standard']);
        $admin = Role::create(['name' => 'admin']);

        $manage_users_permission = Permission::create(['name' => 'manage users']);
        $add_studies_permission = Permission::create(['name' => 'add studies']);

        $permissions = [
            $manage_users_permission,
            $add_studies_permission
        ];

        $admin->syncPermissions($permissions);
        $standard->givePermissionTo('add studies');
    }
}