<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ✅ FIX : birth_date / birth_place / country_birth étaient NOT NULL
     * (sans défaut) depuis la création de la table drivers, mais le
     * formulaire "Nouveau chauffeur" côté société (company.drivers.create)
     * ne collecte ni birth_date/birth_place de façon obligatoire, ni
     * country_birth du tout — ce champ n'existe même pas dans le formulaire.
     * Résultat : chaque création de chauffeur société déclenchait une
     * violation de contrainte SQL ("Column cannot be null") remontée en
     * erreur 500 générique. La société crée un chauffeur "léger" (statut
     * pending) et complète le KYC plus tard — ces champs ne doivent donc
     * pas être obligatoires à la création.
     */
    public function up(): void
    {
        if (!Schema::hasTable('drivers')) {
            return;
        }

        DB::statement("ALTER TABLE drivers MODIFY birth_date DATE NULL");
        DB::statement("ALTER TABLE drivers MODIFY birth_place VARCHAR(255) NULL");
        DB::statement("ALTER TABLE drivers MODIFY country_birth VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('drivers')) {
            return;
        }

        DB::statement("ALTER TABLE drivers MODIFY birth_date DATE NOT NULL");
        DB::statement("ALTER TABLE drivers MODIFY birth_place VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE drivers MODIFY country_birth VARCHAR(255) NOT NULL");
    }
};
