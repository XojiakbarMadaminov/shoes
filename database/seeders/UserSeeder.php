<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Super admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'super@gmail.com'],
            [
                'name'     => 'Super Admin User',
                'password' => bcrypt('123123'),
            ]
        );

        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->roles()->syncWithoutDetaching([$adminRole->id]);

        // admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('123123'),
            ]
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
