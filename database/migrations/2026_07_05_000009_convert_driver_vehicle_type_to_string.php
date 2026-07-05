<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Les types de véhicule sont désormais une liste ouverte (table vehicle_types)
// que les sociétés, les chauffeurs et les admins peuvent enrichir librement.
// La colonne drivers.vehicle_type ne peut donc plus être un ENUM fermé (il
// faudrait sinon une migration à chaque nouveau type) : on la convertit en
// simple chaîne de caractères.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE drivers MODIFY vehicle_type VARCHAR(50) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE drivers MODIFY vehicle_type ENUM('Standard','Confort','Van','PMR','Minibus','Bus','Pickup','SUV','Moto','Utilitaire') NULL");
    }
};
