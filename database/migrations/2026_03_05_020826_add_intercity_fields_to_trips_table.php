<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'available_seats')) {
                $table->integer('available_seats')->default(1)->after('departure_time');
            }
            if (!Schema::hasColumn('trips', 'luggage_kg')) {
                $table->integer('luggage_kg')->default(0)->after('available_seats');
            }
            if (!Schema::hasColumn('trips', 'departure_city')) {
                $table->string('departure_city')->nullable()->after('pickup_address');
            }
            if (!Schema::hasColumn('trips', 'destination_city')) {
                $table->string('destination_city')->nullable()->after('dropoff_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'available_seats',
                'luggage_kg',
                'departure_city',
                'destination_city',
            ]);
        });
    }
};