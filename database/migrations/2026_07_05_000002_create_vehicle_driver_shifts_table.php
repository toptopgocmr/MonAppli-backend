<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_driver_shifts')) {
            Schema::create('vehicle_driver_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();

                // Récurrent : 0=Dimanche ... 6=Samedi. Laisser null si date précise.
                $table->unsignedTinyInteger('day_of_week')->nullable();

                // Date précise (ponctuelle). Laisser null si créneau récurrent.
                $table->date('specific_date')->nullable();

                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();

                $table->boolean('is_primary')->default(false);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['vehicle_id', 'driver_id']);
                $table->index(['driver_id', 'day_of_week']);
                $table->index(['driver_id', 'specific_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_driver_shifts');
    }
};
