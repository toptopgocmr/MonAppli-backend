<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;

return new class extends Migration
{
    /**
     * Un même véhicule (flotte société) peut désormais être partagé par
     * plusieurs chauffeurs (rotation de créneaux). La contrainte unique
     * historique sur drivers.vehicle_plate empêchait ça : on la retire
     * et on la remplace par un simple index (les recherches restent rapides).
     */
    public function up(): void
    {
        if (!Schema::hasTable('drivers')) {
            return;
        }

        $indexes = collect(DB::select("SHOW INDEX FROM drivers"))->pluck('Key_name')->unique();

        if ($indexes->contains('drivers_vehicle_plate_unique')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropUnique('drivers_vehicle_plate_unique');
            });
        }

        if (!$indexes->contains('drivers_vehicle_plate_index')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->index('vehicle_plate');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('drivers')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex('drivers_vehicle_plate_index');
            $table->unique('vehicle_plate');
        });
    }
};
