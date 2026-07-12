<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la modération (offensant / sexuel / inapproprié) au chat support
 * (Admin ↔ Société / Admin ↔ Client / Admin ↔ Chauffeur / Société ↔ Support /
 * Chauffeur ↔ Support), qui jusqu'ici laissait passer n'importe quel contenu
 * tel quel (ex. messages injurieux visibles sans filtre dans le panel admin).
 * Même principe que la table `messages` (chat client ↔ chauffeur), qui a
 * déjà `refused` / `refused_reason`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('support_messages', 'refused')) {
                $table->boolean('refused')->default(false)->after('content');
            }
            if (!Schema::hasColumn('support_messages', 'refused_reason')) {
                $table->string('refused_reason')->nullable()->after('refused');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_messages', 'refused_reason')) {
                $table->dropColumn('refused_reason');
            }
            if (Schema::hasColumn('support_messages', 'refused')) {
                $table->dropColumn('refused');
            }
        });
    }
};
