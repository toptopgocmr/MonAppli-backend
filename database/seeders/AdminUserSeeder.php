<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insertOrIgnore([
            'id'          => 1,
            'name'        => 'Admin',
            'description' => 'Super administrateur',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('admin_users')->insertOrIgnore([
            'first_name' => 'Admin',
            'last_name'  => 'TOPTOPGO',
            'email'      => 'admin@toptopgo.com',
            'password'   => Hash::make('Admin@2026'),
            'role_id'    => 1,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}