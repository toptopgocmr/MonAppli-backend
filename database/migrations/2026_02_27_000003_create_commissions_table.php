<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des commissions.
     * Une commission est générée automatiquement quand une course passe au statut "completed".
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->unique()->constrained('courses')->cascadeOnDelete()
                  ->comment('1 course = 1 commission max');
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Client');
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('commission_rate_id')->nullable()->constrained('commission_rates')->nullOnDelete()
                  ->comment('Taux appliqué au moment du calcul');

            // Calcul
            $table->decimal('montant_course', 12, 2)->comment('Copie du montant_total de la course');
            $table->decimal('taux_applique', 5, 2)->comment('Taux % réellement appliqué (snapshot)');
            $table->decimal('montant_commission', 12, 2)->comment('montant_course * taux_applique / 100');
            $table->string('currency', 10)->default('XAF');

            $table->timestamp('earned_at')->comment('Date/heure de la course complétée');

            $table->timestamps();

            // Index pour les agrégats de revenus
            $table->index(['earned_at']);
            $table->index(['country_id', 'city_id', 'earned_at']);
            $table->index(['driver_id', 'earned_at']);
            $table->index(['user_id', 'earned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};