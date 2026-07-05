<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peex (and any future provider) needs a generic place to store its own
 * transaction id, distinct from Flutterwave's flw_charge_id. We keep using
 * `transaction_ref` (our own reference) as the value sent to Peex as
 * `track_id`, and store Peex's numeric `id` here for reference/debugging.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'provider_transaction_id')) {
                $table->string('provider_transaction_id')->nullable()->after('flw_charge_id')
                      ->comment('Generic provider transaction id (e.g. Peex numeric id)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('provider_transaction_id');
        });
    }
};
