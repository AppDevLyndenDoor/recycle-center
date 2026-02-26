<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class permissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Permission::create(['name' => 'delete row']);
        Permission::create(['name' => 'access settings']);
        Permission::create(['name' => 'change operator']);

        // Create Roles and Assign Permissions
        $role = Role::create(['name' => 'operator']);
        $role->givePermissionTo('change operator');

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['delete row', 'access settings']);
    }
}
