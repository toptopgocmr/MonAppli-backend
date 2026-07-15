<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ✅ FIX : withdrawals.method était un ENUM('mtn','orange','airtel','moov')
     * — hérité d'une toute première version ne couvrant que 4 opérateurs
     * ivoiriens. L'app chauffeur gère aujourd'hui des dizaines de pays avec
     * des noms d'opérateur très variés ("Airtel Congo", "Vodacom", "Orange
     * Belgium", "Djezzy", "AT&T"...) — toute demande de retrait Mobile Money
     * échouait donc en base dès que l'opérateur ne correspondait pas
     * exactement à l'une des 4 valeurs figées. Même logique que la
     * conversion déjà faite sur drivers.vehicle_type (voir
     * 2026_07_05_000009_convert_driver_vehicle_type_to_string.php).
     */
    public function up(): void
    {
        if (!Schema::hasTable('withdrawals') || !Schema::hasColumn('withdrawals', 'method')) {
            return;
        }

        DB::statement("ALTER TABLE withdrawals MODIFY method VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('withdrawals') || !Schema::hasColumn('withdrawals', 'method')) {
            return;
        }

        DB::statement("ALTER TABLE withdrawals MODIFY method ENUM('mtn','orange','airtel','moov') NOT NULL");
    }
};
