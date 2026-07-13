<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ✅ FIX : quand Peex refuse un paiement (ex: solde mobile money insuffisant),
// l'app client affichait toujours le même message générique "Paiement refusé
// ou annulé. Réessayez." car on ne stockait/renvoyait jamais la vraie raison
// fournie par Peex (champ `payment_proof`, ex: "Insufficient balance").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });
    }
};
