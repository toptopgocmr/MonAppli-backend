<?php
/**
 * MIGRATION CORRECTIVE FINALE — trips & bookings
 * 
 * PROBLÈMES RÉSOLUS :
 * 1. departure_time VARCHAR(10) → le controller envoyait "2026-03-06 09:00:00" (19 chars) → SQLSTATE[22001]
 * 2. Colonnes pickup_address/dropoff_address manquaient d'alias departure/destination
 * 3. Colonne luggage_kg vs luggage_included — unification
 * 4. bookings.seats → alias de passengers
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ── 1. Agrandir departure_time pour accepter les deux formats ────────
        // VARCHAR(10) ne peut contenir que "HH:mm:ss" (8 chars) ou "HH:mm" (5 chars)
        // On passe à VARCHAR(20) pour être safe, le controller devra envoyer seulement l'heure
        DB::statement("ALTER TABLE trips MODIFY departure_time VARCHAR(20) NULL");

        // ── 2. Ajouter colonnes alias departure/destination ──────────────────
        Schema::table('trips', function (Blueprint $table) {
            // Alias lisibles pour le Flutter (évite confusion pickup_address/departure)
            if (!Schema::hasColumn('trips', 'departure')) {
                $table->string('departure')->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('trips', 'destination')) {
                $table->string('destination')->nullable()->after('dropoff_address');
            }

            // Poids bagage unifié
            if (!Schema::hasColumn('trips', 'luggage_weight_kg')) {
                $table->decimal('luggage_weight_kg', 6, 2)->nullable()->default(0)->after('luggage_included');
            }
        });

        // ── 3. Synchroniser pickup_address ↔ departure pour les trips existants
        DB::statement("UPDATE trips SET departure = pickup_address WHERE departure IS NULL AND pickup_address IS NOT NULL");
        DB::statement("UPDATE trips SET destination = dropoff_address WHERE destination IS NULL AND dropoff_address IS NOT NULL");
        DB::statement("UPDATE trips SET pickup_address = departure WHERE pickup_address IS NULL AND departure IS NOT NULL");
        DB::statement("UPDATE trips SET dropoff_address = destination WHERE dropoff_address IS NULL AND destination IS NOT NULL");

        // ── 4. Bookings: ajouter alias seats = passengers ────────────────────
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'seats')) {
                $table->integer('seats')->default(1)->after('passengers');
            }
            if (!Schema::hasColumn('bookings', 'luggage_count')) {
                $table->integer('luggage_count')->default(0)->after('seats');
            }
            if (!Schema::hasColumn('bookings', 'luggage_fee')) {
                $table->decimal('luggage_fee', 10, 2)->default(0)->after('luggage_count');
            }
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                    ->default('pending')
                    ->after('status');
            }
        });

        // Synchroniser seats ↔ passengers
        DB::statement("UPDATE bookings SET seats = passengers WHERE seats = 1 AND passengers > 1");
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['departure', 'destination', 'luggage_weight_kg']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['seats', 'luggage_count', 'luggage_fee', 'payment_status']);
        });
        DB::statement("ALTER TABLE trips MODIFY departure_time VARCHAR(10) NULL");
    }
};
