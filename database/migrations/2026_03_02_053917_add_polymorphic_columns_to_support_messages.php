<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            // Ajouter sender_type et sender_id si manquants
            if (!Schema::hasColumn('support_messages', 'sender_type')) {
                $table->string('sender_type')->after('id')->nullable();
            }
            if (!Schema::hasColumn('support_messages', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->after('sender_type')->nullable();
            }

            // Ajouter recipient_type si manquant
            if (!Schema::hasColumn('support_messages', 'recipient_type')) {
                $table->string('recipient_type')->after('sender_id')->nullable();
            }

            // Ajouter trip_id pour rattacher le message à un trajet
            if (!Schema::hasColumn('support_messages', 'trip_id')) {
                $table->foreignId('trip_id')
                      ->nullable()
                      ->constrained('trips')
                      ->onDelete('cascade')
                      ->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_messages', 'sender_type')) {
                $table->dropColumn('sender_type');
            }
            if (Schema::hasColumn('support_messages', 'sender_id')) {
                $table->dropColumn('sender_id');
            }
            if (Schema::hasColumn('support_messages', 'recipient_type')) {
                $table->dropColumn('recipient_type');
            }
            if (Schema::hasColumn('support_messages', 'trip_id')) {
                $table->dropForeign(['trip_id']); // supprime la contrainte
                $table->dropColumn('trip_id');
            }
        });
    }
};