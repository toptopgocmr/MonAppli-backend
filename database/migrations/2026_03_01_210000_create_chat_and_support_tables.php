<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── TABLE MESSAGES DE TRAJET
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
                $table->morphs('sender');    // sender_type + sender_id
                $table->morphs('receiver');  // receiver_type + receiver_id
                $table->text('content');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // ── TABLE SUPPORT MESSAGES (mise à jour si elle existe déjà)
        if (Schema::hasTable('support_messages')) {
            Schema::table('support_messages', function (Blueprint $table) {
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
        } else {
            // si elle n’existe pas, on la crée complète
            Schema::create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->morphs('sender');
                $table->morphs('recipient');
                $table->text('content');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // ── TABLE APPELS
        if (!Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
                $table->morphs('caller');
                $table->morphs('receiver');
                $table->enum('type', ['audio', 'video']);
                $table->enum('status', ['initiated', 'answered', 'missed', 'ended'])->default('initiated');
                $table->integer('duration_seconds')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
        Schema::dropIfExists('messages');

        if (Schema::hasTable('support_messages')) {
            Schema::table('support_messages', function (Blueprint $table) {
                $table->dropColumn(['sender_type', 'sender_id', 'recipient_type', 'recipient_id', 'trip_id']);
            });
        }
    }
};