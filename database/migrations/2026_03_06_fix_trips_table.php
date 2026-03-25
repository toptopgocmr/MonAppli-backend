<?php
// CHEMIN: database/migrations/2026_03_06_000001_fix_trips_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {

            // FIX 1 — vehicle_type tronqué → varchar(100)
            $table->string('vehicle_type', 100)->nullable()->change();

            // FIX 2 — colonnes manquantes pour le client
            if (!Schema::hasColumn('trips', 'price_per_seat')) {
                $table->decimal('price_per_seat', 10, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('trips', 'pickup_address')) {
                $table->string('pickup_address')->nullable()->after('departure');
            }
            if (!Schema::hasColumn('trips', 'dropoff_address')) {
                $table->string('dropoff_address')->nullable()->after('destination');
            }
            if (!Schema::hasColumn('trips', 'luggage_weight_kg')) {
                $table->decimal('luggage_weight_kg', 8, 2)->default(20)->after('luggage_included');
            }
            if (!Schema::hasColumn('trips', 'extra_luggage_slots')) {
                $table->integer('extra_luggage_slots')->default(0)->after('extra_luggage_fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('vehicle_type', 50)->nullable()->change();
        });
    }
};
