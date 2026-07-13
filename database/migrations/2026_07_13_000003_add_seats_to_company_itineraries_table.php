<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capacité (nombre de places) d'un itinéraire programmé par une société.
 * Nécessaire pour permettre la réservation + paiement instantané côté
 * client (avant, seul un appel à la société était possible — voir
 * UserCompanyTripController::book()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_itineraries', function (Blueprint $table) {
            if (!Schema::hasColumn('company_itineraries', 'seats')) {
                $table->integer('seats')->nullable()->default(4)->after('vehicle_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('company_itineraries', 'seats')) {
                $table->dropColumn('seats');
            }
        });
    }
};
