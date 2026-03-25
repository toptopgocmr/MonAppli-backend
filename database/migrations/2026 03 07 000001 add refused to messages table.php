<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes de modération à la table messages :
 *   - refused        : message bloqué par la modération (défaut false)
 *   - refused_reason : raison du blocage (numéro, insulte, menace...)
 *
 * Lancez : php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'refused')) {
                $table->boolean('refused')
                    ->default(false)
                    ->after('is_read')
                    ->comment('true = bloqué par la modération');
            }

            if (!Schema::hasColumn('messages', 'refused_reason')) {
                $table->string('refused_reason')
                    ->nullable()
                    ->after('refused')
                    ->comment('numéro de téléphone | lien | insulte | menace...');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $cols = array_filter(
                ['refused', 'refused_reason'],
                fn($col) => Schema::hasColumn('messages', $col)
            );
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};