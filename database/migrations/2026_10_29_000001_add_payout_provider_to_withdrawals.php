<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace la passerelle qui a payé un retrait (peex|wacepay|manual) et le
 * statut brut renvoyé par le partenaire (WacePay : PENDING, PROCESSING,
 * PAID, CANCEL…), pour que wacepay:sync-payouts puisse suivre le paiement
 * après l'approbation et rembourser si WacePay l'annule.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['withdrawals', 'company_withdrawals'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'provider')) {
                    $t->string('provider', 30)->nullable()->after('transaction_ref');
                }
                if (!Schema::hasColumn($table, 'provider_status')) {
                    $t->string('provider_status', 40)->nullable()->after('provider');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['withdrawals', 'company_withdrawals'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['provider_status', 'provider'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
