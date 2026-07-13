<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ✅ FIX : un trajet issu d'un itinéraire société réservé côté client
 * (UserCompanyTripController::book()) n'a pas encore de chauffeur au moment
 * où le client paie — c'est le principe même du flux (le client paie, la
 * société assigne le chauffeur ensuite). Or payments.driver_id était
 * NOT NULL, donc UserPaymentController::mobileMoney() plantait avec
 * "SQLSTATE[23000]: Column 'driver_id' cannot be null" dès qu'un client
 * tentait de payer une réservation sur ce type de trajet.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments MODIFY driver_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Ne repasse pas en NOT NULL au rollback : des lignes avec driver_id
        // NULL peuvent déjà exister en base (paiements sur trajets pas
        // encore assignés à un chauffeur).
    }
};
