<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Permet à un trajet d'exister temporairement SANS chauffeur assigné.
 *
 * Contexte : un client peut désormais réserver + payer un "itinéraire
 * programmé" publié par une société (App\Models\CompanyItinerary), qui n'a
 * pas de chauffeur précis au moment de la réservation — la société assigne
 * le chauffeur ensuite depuis son dashboard (voir Company\ReservationController
 * ::assignDriver). Avant cette migration, trips.driver_id était NOT NULL,
 * ce qui rendait ce flux impossible ; le modèle Trip::driver() utilisait
 * déjà ->withDefault([...]) en anticipation de ce cas.
 *
 * company_id / company_itinerary_id permettent à la société de retrouver
 * ses trajets en attente de chauffeur même avant qu'un driver_id existe
 * (sinon aucun lien vers la société n'est possible, puisque Trip->company
 * ne passe normalement que par driver->company_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE trips MODIFY driver_id BIGINT UNSIGNED NULL');

        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('driver_id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('trips', 'company_itinerary_id')) {
                $table->foreignId('company_itinerary_id')->nullable()->after('company_id')
                    ->constrained('company_itineraries')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'company_itinerary_id')) {
                $table->dropConstrainedForeignId('company_itinerary_id');
            }
            if (Schema::hasColumn('trips', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
        // NB: on ne repasse pas driver_id en NOT NULL au rollback (des lignes
        // avec driver_id NULL pourraient déjà exister en base).
    }
};
