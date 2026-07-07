<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table calls — appels voix in-app (client <-> chauffeur).
 *
 * Référencée par DriverCallController (App\Models\Call) mais n'existait
 * jusqu'ici dans aucune migration : tout appel initié par un chauffeur
 * provoquait une erreur fatale (classe/table introuvable).
 *
 * caller_type/caller_id et receiver_type/receiver_id sont des colonnes
 * polymorphiques (morphTo) : l'appelant et le destinataire peuvent être
 * soit App\Models\User\User, soit App\Models\Driver\Driver, selon le sens
 * de l'appel.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('calls')) {
            return;
        }

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');

            $table->string('caller_type');
            $table->unsignedBigInteger('caller_id');
            $table->string('receiver_type');
            $table->unsignedBigInteger('receiver_id');

            $table->string('type')->default('audio'); // audio | video
            $table->string('status')->default('initiated'); // initiated | answered | ended | missed

            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index(['caller_type', 'caller_id']);
            $table->index(['receiver_type', 'receiver_id']);
            $table->index(['trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
