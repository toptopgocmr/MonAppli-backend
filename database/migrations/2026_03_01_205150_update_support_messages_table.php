<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            // Ajouter des colonnes si elles n'existent pas encore
            if (!Schema::hasColumn('support_messages', 'sender_type')) {
                $table->string('sender_type')->after('id');
            }
            if (!Schema::hasColumn('support_messages', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->after('sender_type');
            }
            if (!Schema::hasColumn('support_messages', 'recipient_type')) {
                $table->string('recipient_type')->after('sender_id');
            }
            if (!Schema::hasColumn('support_messages', 'recipient_id')) {
                $table->unsignedBigInteger('recipient_id')->after('recipient_type');
            }
            if (!Schema::hasColumn('support_messages', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade')->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['sender_type', 'sender_id', 'recipient_type', 'recipient_id', 'trip_id']);
        });
    }
};