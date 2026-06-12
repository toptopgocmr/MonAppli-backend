<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_itineraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('departure');                    // Ville de départ
            $table->string('departure_point')->nullable();  // Point précis d'embarquement
            $table->time('departure_time')->nullable();     // Heure de départ
            $table->string('destination');                  // Ville d'arrivée
            $table->string('arrival_point')->nullable();    // Point précis de débarquement
            $table->time('arrival_time')->nullable();       // Heure d'arrivée estimée
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_min')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_itineraries');
    }
};
