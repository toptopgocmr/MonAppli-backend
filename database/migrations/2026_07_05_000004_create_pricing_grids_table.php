<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pricing_grids')) {
            Schema::create('pricing_grids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pricing_grid_rates')) {
            Schema::create('pricing_grid_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pricing_grid_id')->constrained('pricing_grids')->cascadeOnDelete();
                // Libellé libre : "Standard", "0-10km", "Nuit", etc. — rempli manuellement.
                $table->string('label');
                $table->string('vehicle_type')->nullable();
                $table->decimal('price', 10, 2);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('company_itineraries') && !Schema::hasColumn('company_itineraries', 'pricing_grid_id')) {
            Schema::table('company_itineraries', function (Blueprint $table) {
                $table->foreignId('pricing_grid_id')->nullable()->after('company_id')
                      ->constrained('pricing_grids')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_itineraries') && Schema::hasColumn('company_itineraries', 'pricing_grid_id')) {
            Schema::table('company_itineraries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pricing_grid_id');
            });
        }
        Schema::dropIfExists('pricing_grid_rates');
        Schema::dropIfExists('pricing_grids');
    }
};
