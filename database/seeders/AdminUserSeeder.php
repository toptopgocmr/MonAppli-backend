<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\AdminUser;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'Admin')->first();

        AdminUser::firstOrCreate(
            ['email' => 'admin@toptopgo.com'],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'phone'      => '+242000000000',
                'role_id'    => $role->id,
                'password'   => Hash::make('Admin@1234'),
                'status'     => 'active',
            ]
        );
    }
}
