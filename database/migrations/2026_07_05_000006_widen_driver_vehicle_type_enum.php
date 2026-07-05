<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Élargit l'enum drivers.vehicle_type pour couvrir les nouveaux types de
     * véhicule proposés dans la flotte société (Vehicle::TYPES), sans casser
     * les valeurs déjà en base (Standard/Confort/Van/PMR restent valides).
     */
    public function up(): void
    {
        if (!Schema::hasTable('drivers') || !Schema::hasColumn('drivers', 'vehicle_type')) {
            return;
        }

        DB::statement("ALTER TABLE drivers MODIFY vehicle_type ENUM('Standard','Confort','Van','PMR','Minibus','Bus','Pickup','SUV','Moto','Utilitaire') NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('drivers') || !Schema::hasColumn('drivers', 'vehicle_type')) {
            return;
        }

        DB::statement("ALTER TABLE drivers MODIFY vehicle_type ENUM('Standard','Confort','Van','PMR') NULL");
    }
};
