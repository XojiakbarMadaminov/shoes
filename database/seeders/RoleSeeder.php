<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        Role::updateOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        Artisan::call('shield:generate --panel=admin --option=permissions');

        $allPermissions = Permission::all();
        $superAdmin->syncPermissions($allPermissions);
    }
}
