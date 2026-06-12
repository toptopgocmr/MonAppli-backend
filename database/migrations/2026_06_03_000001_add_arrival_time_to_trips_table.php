<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter arrival_time si absent
        if (!Schema::hasColumn('trips', 'arrival_time')) {
            Schema::table('trips', function (Blueprint $table) {
                $table->string('arrival_time', 20)->nullable()->after('departure_time');
            });
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('arrival_time');
        });
    }
};
