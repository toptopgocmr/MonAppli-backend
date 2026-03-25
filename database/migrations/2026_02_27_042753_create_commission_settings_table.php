<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 5, 2)->default(10.00);
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->onDelete('set null');
            $table->timestamps();
        });

        // Taux par défaut 10%
        DB::table('commission_settings')->insert([
            'rate'        => 10.00,
            'description' => 'Taux de commission par défaut',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};