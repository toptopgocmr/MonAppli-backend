<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->index(['recipient_type', 'recipient_id', 'is_read']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['recipient_type', 'recipient_id', 'is_read']);
            $table->dropIndex(['sender_type', 'sender_id']);
        });
    }
};