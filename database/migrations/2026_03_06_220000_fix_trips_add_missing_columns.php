<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * MIGRATION CORRECTIVE FINALE — trips
 *
 * Ajoute toutes les colonnes manquantes utilisées par l'app chauffeur :
 * pickup_point, dropoff_point, departure, destination,
 * departure_city, destination_city, et tous les champs bagages/tarification.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Modifier vehicle_type : ENUM → VARCHAR pour accepter n'importe quel modèle
        DB::statement("ALTER TABLE trips MODIFY vehicle_type VARCHAR(100) NULL");

        // ── 2. Modifier departure_time : VARCHAR(10) → VARCHAR(20)
        DB::statement("ALTER TABLE trips MODIFY departure_time VARCHAR(20) NULL");

        // ── 3. Rendre user_id nullable (trajet créé par chauffeur sans user)
        DB::statement("ALTER TABLE trips MODIFY user_id BIGINT UNSIGNED NULL");

        // ── 4. Rendre coordonnées nullables
        DB::statement("ALTER TABLE trips MODIFY pickup_lat  DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY pickup_lng  DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY dropoff_lat DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY dropoff_lng DECIMAL(10,7) NULL");

        // ── 5. Rendre amount nullable
        DB::statement("ALTER TABLE trips MODIFY amount DECIMAL(10,2) NULL DEFAULT 0");

        // ── 6. Ajouter toutes les colonnes manquantes
        Schema::table('trips', function (Blueprint $table) {

            // Villes (noms courts pour affichage)
            if (!Schema::hasColumn('trips', 'departure')) {
                $table->string('departure')->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('trips', 'destination')) {
                $table->string('destination')->nullable()->after('dropoff_address');
            }
            if (!Schema::hasColumn('trips', 'departure_city')) {
                $table->string('departure_city')->nullable()->after('departure');
            }
            if (!Schema::hasColumn('trips', 'destination_city')) {
                $table->string('destination_city')->nullable()->after('destination');
            }

            // ✅ Lieux précis de prise en charge / dépose
            if (!Schema::hasColumn('trips', 'pickup_point')) {
                $table->string('pickup_point')->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('trips', 'dropoff_point')) {
                $table->string('dropoff_point')->nullable()->after('dropoff_address');
            }

            // Date et heure de départ
            if (!Schema::hasColumn('trips', 'departure_date')) {
                $table->date('departure_date')->nullable()->after('destination_city');
            }
            if (!Schema::hasColumn('trips', 'departure_time')) {
                $table->string('departure_time', 20)->nullable()->after('departure_date');
            }

            // Tarification
            if (!Schema::hasColumn('trips', 'price_per_seat')) {
                $table->decimal('price_per_seat', 10, 2)->nullable()->default(0)->after('amount');
            }
            if (!Schema::hasColumn('trips', 'available_seats')) {
                $table->integer('available_seats')->nullable()->default(1)->after('price_per_seat');
            }

            // Bagages inclus
            if (!Schema::hasColumn('trips', 'luggage_included')) {
                $table->integer('luggage_included')->nullable()->default(1)->after('available_seats');
            }
            if (!Schema::hasColumn('trips', 'luggage_weight_kg')) {
                $table->decimal('luggage_weight_kg', 8, 2)->nullable()->default(20)->after('luggage_included');
            }

            // Bagages excédentaires
            if (!Schema::hasColumn('trips', 'extra_luggage_slots')) {
                $table->integer('extra_luggage_slots')->nullable()->default(0)->after('luggage_weight_kg');
            }
            if (!Schema::hasColumn('trips', 'extra_luggage_fee')) {
                $table->decimal('extra_luggage_fee', 10, 2)->nullable()->default(0)->after('extra_luggage_slots');
            }

            // Statut étendu
            if (!Schema::hasColumn('trips', 'active')) {
                $table->boolean('active')->default(true)->after('status');
            }
        });

        // ── 7. Synchroniser pickup_address ↔ departure pour les données existantes
        DB::statement("UPDATE trips SET departure      = pickup_address  WHERE departure IS NULL  AND pickup_address  IS NOT NULL");
        DB::statement("UPDATE trips SET destination    = dropoff_address WHERE destination IS NULL AND dropoff_address IS NOT NULL");
        DB::statement("UPDATE trips SET departure_city = pickup_address  WHERE departure_city IS NULL AND pickup_address IS NOT NULL");
        DB::statement("UPDATE trips SET destination_city = dropoff_address WHERE destination_city IS NULL AND dropoff_address IS NOT NULL");
        DB::statement("UPDATE trips SET price_per_seat = amount WHERE price_per_seat = 0 AND amount > 0");
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'departure', 'destination',
                'departure_city', 'destination_city',
                'pickup_point', 'dropoff_point',
                'departure_date', 'departure_time',
                'price_per_seat', 'available_seats',
                'luggage_included', 'luggage_weight_kg',
                'extra_luggage_slots', 'extra_luggage_fee',
                'active',
            ]);
        });
        DB::statement("ALTER TABLE trips MODIFY vehicle_type ENUM('Standard','Confort','Van','PMR') NULL");
        DB::statement("ALTER TABLE trips MODIFY departure_time VARCHAR(10) NULL");
    }
};