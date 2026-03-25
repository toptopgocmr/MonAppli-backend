<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trips MODIFY user_id BIGINT UNSIGNED NULL");

        DB::statement("ALTER TABLE trips MODIFY pickup_lat DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY pickup_lng DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY dropoff_lat DECIMAL(10,7) NULL");
        DB::statement("ALTER TABLE trips MODIFY dropoff_lng DECIMAL(10,7) NULL");

        DB::statement("ALTER TABLE trips MODIFY amount DECIMAL(10,2) NULL DEFAULT 0");

        Schema::table('trips', function (Blueprint $table) {

            if (!Schema::hasColumn('trips','price_per_seat')) {
                $table->decimal('price_per_seat',10,2)->nullable()->default(0);
            }

            if (!Schema::hasColumn('trips','available_seats')) {
                $table->integer('available_seats')->nullable()->default(1);
            }

            if (!Schema::hasColumn('trips','departure_date')) {
                $table->date('departure_date')->nullable();
            }

            if (!Schema::hasColumn('trips','departure_time')) {
                $table->string('departure_time',10)->nullable();
            }

            if (!Schema::hasColumn('trips','luggage_included')) {
                $table->integer('luggage_included')->nullable()->default(1);
            }

            if (!Schema::hasColumn('trips','extra_luggage_fee')) {
                $table->decimal('extra_luggage_fee',10,2)->nullable()->default(0);
            }

        });
    }

    public function down(): void {}
};