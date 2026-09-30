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
        $password = config('attendance.admin_seed_password');
        if (! $password) {
            if (app()->environment('production')) {
                throw new \RuntimeException('Set ADMIN_SEED_PASSWORD before seeding in production.');
            }
            $password = 'ChangeMe!12345'; // local development only
        }

        $admin = User::firstOrCreate(
            ['email' => config('attendance.admin_seed_email')],
            [
                'name' => 'QRCAP Super Admin',
                'password' => Hash::make($password),
                'account_status' => 'active',
            ]
        );

        $role = Role::where('name', 'super_admin')->first();
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }
}