<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'Admin'              => ['view_payments', 'approve_driver', 'access_dashboard', 'manage_documents', 'respond_tickets', 'view_statistics'],
            'Finance Manager'    => ['view_payments', 'access_dashboard'],
            'Compliance Manager' => ['approve_driver', 'manage_documents', 'access_dashboard'],
            'Technical Support'  => ['respond_tickets', 'access_dashboard'],
            'Commercial Manager' => ['view_statistics', 'access_dashboard'],
            'Chauffeur'          => [],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;
            $ids = Permission::whereIn('name', $permissions)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}
