<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'attendee', 'label' => 'Attendee'],
            ['name' => 'staff', 'label' => 'Staff'],
            ['name' => 'admin', 'label' => 'Admin'],
            ['name' => 'super_admin', 'label' => 'Super Admin'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}