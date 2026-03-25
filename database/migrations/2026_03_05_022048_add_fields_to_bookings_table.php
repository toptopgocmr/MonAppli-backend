<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'passengers')) {
                $table->integer('passengers')->default(1)->after('trip_id');
            }
            if (!Schema::hasColumn('bookings', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('passengers');
            }
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','accepted','rejected','cancelled','paid','completed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['passengers', 'amount']);
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled') DEFAULT 'pending'");
    }
};