<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_types')) {
            Schema::create('vehicle_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                // Qui a ajouté ce type : 'system' (liste de base), 'admin', 'company', 'driver'.
                $table->string('added_by_type')->nullable();
                $table->unsignedBigInteger('added_by_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Fusion de toutes les listes de types de véhicules déjà présentes en dur
        // dans l'application (flotte société, chauffeurs individuels, grilles
        // tarifaires, itinéraires) pour ne pas perdre d'options existantes.
        $seed = [
            'Standard', 'Confort', 'Van', 'PMR', 'Minibus', 'Bus',
            'Pickup', 'SUV', 'Moto', 'Utilitaire', 'Berline', 'Monospace', 'Tricycle',
        ];

        foreach ($seed as $name) {
            DB::table('vehicle_types')->insertOrIgnore([
                'name'          => $name,
                'added_by_type' => 'system',
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
