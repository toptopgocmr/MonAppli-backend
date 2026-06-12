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
            $table->string('departure');           // Ville / lieu de départ
            $table->string('destination');         // Ville / lieu d'arrivée
            $table->decimal('price', 10, 2)->nullable();   // Tarif indicatif
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_min')->nullable();   // Durée estimée en minutes
            $table->string('vehicle_type')->nullable();    // Type de véhicule suggéré
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
