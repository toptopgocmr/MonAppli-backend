<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'provider')) {
                $table->string('provider')->nullable()->after('method')
                      ->comment('flutterwave | stripe | mtn_momo | airtel_money');
            }

            if (!Schema::hasColumn('payments', 'flw_charge_id')) {
                $table->string('flw_charge_id')->nullable()->after('transaction_ref')
                      ->comment('ID de charge Flutterwave (chg_xxx)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'flw_charge_id']);
        });
    }
};
