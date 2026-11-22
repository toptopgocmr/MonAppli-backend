<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frais de service TopTopGo payés par le client en plus du prix de la course
 * (figés sur la réservation, enregistrés sur le paiement).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['bookings', 'payments'] as $name) {
            if (Schema::hasTable($name) && !Schema::hasColumn($name, 'service_fee')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->decimal('service_fee', 12, 2)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['bookings', 'payments'] as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'service_fee')) {
                Schema::table($name, fn (Blueprint $table) => $table->dropColumn('service_fee'));
            }
        }
    }
};
