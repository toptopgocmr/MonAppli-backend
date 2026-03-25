<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            // Acteurs
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Client');
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete()->comment('Chauffeur');

            // Localisation
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();

            // Trajet
            $table->string('pickup_address')->comment('Adresse de départ');
            $table->string('dropoff_address')->comment('Adresse d\'arrivée');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable()->comment('Distance en kilomètres');

            // Financier
            $table->decimal('montant_total', 12, 2)->comment('Montant total payé par le client');
            $table->string('currency', 10)->default('XAF');

            // Statut
            $table->enum('status', [
                'pending',      // En attente de chauffeur
                'accepted',     // Acceptée par le chauffeur
                'in_progress',  // En cours
                'completed',    // Terminée (commission calculée)
                'cancelled',    // Annulée
            ])->default('pending');

            $table->timestamp('started_at')->nullable()->comment('Heure de début de course');
            $table->timestamp('completed_at')->nullable()->comment('Heure de fin de course');
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['status', 'created_at']);
            $table->index(['driver_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['country_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};