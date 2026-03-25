<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_payments', 'description' => 'Voir les paiements'],
            ['name' => 'approve_driver', 'description' => 'Approuver les chauffeurs'],
            ['name' => 'access_dashboard', 'description' => 'Accéder au dashboard'],
            ['name' => 'manage_documents', 'description' => 'Gérer les documents'],
            ['name' => 'respond_tickets', 'description' => 'Répondre aux tickets support'],
            ['name' => 'view_statistics', 'description' => 'Voir statistiques et reporting'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
