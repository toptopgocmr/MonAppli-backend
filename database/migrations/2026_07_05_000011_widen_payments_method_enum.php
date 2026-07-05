<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Élargit l'enum payments.method pour couvrir tous les opérateurs Mobile
     * Money réellement proposés par l'app mobile client (Wave, Free Money,
     * Mobicash, Vodafone/Telecel, M-Pesa, Zamtel, TNM, Halopesa, AirtelTigo,
     * Tigo Pesa), sans casser les valeurs déjà en base.
     *
     * Avant ce correctif, UserPaymentController::mapNetwork() faisait
     * retomber tous ces opérateurs sur 'mtn' par défaut faute de valeur
     * d'enum correspondante, ce qui faussait aussi bien les statistiques
     * admin que l'opérateur réellement transmis à Flutterwave.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasColumn('payments', 'method')) {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY method ENUM(
            'mtn','orange','airtel','moov','visa','mastercard',
            'wave','free','mobicash','vodafone','mpesa','zamtel','tnm','halopesa','airteltigo','tigo'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasColumn('payments', 'method')) {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY method ENUM('mtn','orange','airtel','moov','visa','mastercard') NOT NULL");
    }
};
