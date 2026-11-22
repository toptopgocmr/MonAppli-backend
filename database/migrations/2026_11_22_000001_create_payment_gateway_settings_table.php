<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passerelles de paiement réglables depuis l'admin (défaut + secours)
 * et historique des tentatives (bascules) sur chaque paiement / retrait.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateway_settings')) {
            Schema::create('payment_gateway_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('value', 255)->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        foreach (['payments', 'withdrawals', 'company_withdrawals'] as $name) {
            if (Schema::hasTable($name) && !Schema::hasColumn($name, 'gateway_attempts')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->json('gateway_attempts')->nullable();
                });
            }
        }

        // Pays choisi par le chauffeur pour son retrait (jusqu'ici ignoré).
        if (Schema::hasTable('withdrawals') && !Schema::hasColumn('withdrawals', 'country')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $table->string('country', 5)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['payments', 'withdrawals', 'company_withdrawals'] as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'gateway_attempts')) {
                Schema::table($name, fn (Blueprint $table) => $table->dropColumn('gateway_attempts'));
            }
        }
        if (Schema::hasTable('withdrawals') && Schema::hasColumn('withdrawals', 'country')) {
            Schema::table('withdrawals', fn (Blueprint $table) => $table->dropColumn('country'));
        }
        Schema::dropIfExists('payment_gateway_settings');
    }
};
