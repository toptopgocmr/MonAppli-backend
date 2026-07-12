<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support de l'enregistrement des appels client↔chauffeur (mobile↔mobile,
 * aucune jambe web ne peut les enregistrer via MediaRecorder comme pour les
 * appels support). Utilise Agora Cloud Recording (bot serveur qui rejoint le
 * canal et uploade directement vers un bucket S3 dédié).
 *
 * - `calls` : identifiants de session Cloud Recording, nécessaires entre le
 *   `start()` (à la réponse) et le `stop()` (au raccroché), deux requêtes
 *   HTTP séparées dans le temps.
 * - `call_recordings` : `recorded_by_type`/`recorded_by_id` deviennent
 *   nullable (un enregistrement Cloud Recording n'est "enregistré par"
 *   aucune personne précise, contrairement aux enregistrements navigateur
 *   admin/société) ; `source` distingue 'browser' (MediaRecorder, disque
 *   `public`) de 'cloud' (Agora Cloud Recording, disque `agora_recordings`).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'recording_resource_id')) {
                $table->string('recording_resource_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('calls', 'recording_sid')) {
                $table->string('recording_sid')->nullable()->after('recording_resource_id');
            }
            if (!Schema::hasColumn('calls', 'recording_uid')) {
                $table->unsignedBigInteger('recording_uid')->nullable()->after('recording_sid');
            }
        });

        Schema::table('call_recordings', function (Blueprint $table) {
            if (!Schema::hasColumn('call_recordings', 'source')) {
                $table->string('source')->default('browser')->after('call_id'); // 'browser' | 'cloud'
            }
            if (!Schema::hasColumn('call_recordings', 'storage_disk')) {
                $table->string('storage_disk')->default('public')->after('path');
            }
        });

        // recorded_by_type / recorded_by_id nullable (MySQL/Postgres via change()).
        try {
            Schema::table('call_recordings', function (Blueprint $table) {
                $table->string('recorded_by_type')->nullable()->change();
                $table->unsignedBigInteger('recorded_by_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Si doctrine/dbal n'est pas installé, ->change() n'est pas
            // disponible — non bloquant : les enregistrements 'cloud'
            // pourront simplement mettre 0/'' au lieu de null.
        }
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['recording_resource_id', 'recording_sid', 'recording_uid']);
        });
        Schema::table('call_recordings', function (Blueprint $table) {
            $table->dropColumn(['source', 'storage_disk']);
        });
    }
};
