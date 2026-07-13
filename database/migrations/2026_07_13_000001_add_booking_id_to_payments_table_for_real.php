<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ Malgré son nom, la migration 2026_03_05_022633_add_booking_id_to_payments_table
 * n'a JAMAIS ajouté de colonne booking_id à la table payments (elle modifie en
 * réalité la table bookings — passengers/amount/status). Le modèle Payment
 * (fillable) et UserPaymentController::mobileMoney()/status() utilisent
 * pourtant `booking_id` depuis le début, ce qui provoquait une erreur 500
 * ("SQLSTATE[42S22]: Column not found: 1054 Unknown column 'booking_id'")
 * dès qu'un client tentait de payer un trajet via /user/payments/mobile-money.
 * Cette migration ajoute réellement la colonne manquante.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'booking_id')) {
                $table->foreignId('booking_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('bookings')
                    ->nullOnDelete();
                $table->index('booking_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'booking_id')) {
                $table->dropConstrainedForeignId('booking_id');
            }
        });
    }
};
