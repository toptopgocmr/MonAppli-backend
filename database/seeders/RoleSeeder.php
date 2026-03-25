<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'description' => 'Super Admin, accès total'],
            ['name' => 'Compliance Manager', 'description' => 'Vérification documents et sécurité'],
            ['name' => 'Finance Manager', 'description' => 'Gestion paiements et eWallet'],
            ['name' => 'Commercial Manager', 'description' => 'Statistiques et reporting'],
            ['name' => 'Technical Support', 'description' => 'Support et tickets techniques'],
            ['name' => 'Partenaire', 'description' => 'Chauffeur application mobile'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
