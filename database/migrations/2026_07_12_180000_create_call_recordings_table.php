<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enregistrement des appels — un appel peut avoir jusqu'à 2 enregistrements
 * (un par côté, admin/société/client/chauffeur, chacun capturant son micro +
 * l'audio distant reçu via l'API Web Audio, voir *-call-widget.blade.php).
 * Stockés sur le disque `local` (privé, jamais exposé en URL publique) et
 * servis via une route authentifiée avec vérification d'appartenance.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('call_recordings')) {
            Schema::create('call_recordings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
                $table->string('recorded_by_type'); // App\Models\Admin\AdminUser, App\Models\Company, ...
                $table->unsignedBigInteger('recorded_by_id');
                $table->string('path'); // chemin relatif sur le disque `local`
                $table->unsignedInteger('size_bytes')->nullable();
                $table->timestamps();

                $table->index(['call_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('call_recordings');
    }
};
