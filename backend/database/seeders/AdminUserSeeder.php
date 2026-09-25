<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates one super_admin login for first access.
     * CHANGE THIS PASSWORD immediately after your first login.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@qrcap.test'],
            [
                'name' => 'QRCAP Super Admin',
                'password' => Hash::make('ChangeMe!12345'),
                'account_status' => 'active',
            ]
        );

        $role = Role::where('name', 'super_admin')->first();
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }
}