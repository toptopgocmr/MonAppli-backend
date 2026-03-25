<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Supprimer l'ancienne table si elle existe
        Schema::dropIfExists('commission_settings');

        Schema::create('commission_rates', function (Blueprint $table) {
            $table->id();

            // Type de règle
            $table->enum('type', ['global', 'country', 'vehicle_type', 'driver'])
                  ->default('global')
                  ->comment('Niveau de la règle');

            // Cibles (null = s'applique à tous)
            $table->string('country')->nullable()->comment('Pays ciblé');
            $table->enum('vehicle_type', ['Standard', 'Confort', 'Van', 'PMR'])->nullable()->comment('Type véhicule ciblé');
            $table->unsignedBigInteger('driver_id')->nullable()->comment('Chauffeur ciblé');
            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');

            // Taux
            $table->decimal('rate', 5, 2)->comment('Taux en %');

            // Meta
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->onDelete('set null');
            $table->timestamps();

            // Unicité par combinaison
            $table->unique(['type', 'country', 'vehicle_type', 'driver_id'], 'unique_commission_rule');
        });

        // Taux global par défaut : 10%
        DB::table('commission_rates')->insert([
            'type'        => 'global',
            'rate'        => 10.00,
            'description' => 'Taux global par défaut',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rates');
    }
};