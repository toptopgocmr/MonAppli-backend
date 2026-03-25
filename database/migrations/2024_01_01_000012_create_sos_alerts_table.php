<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->morphs('sender');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['active', 'treated'])->default('active');
            $table->foreignId('treated_by')->nullable()->constrained('admin_users')->onDelete('set null');
            $table->timestamp('treated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->enum('driver_status', ['online', 'pause', 'offline'])->default('offline');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admin_users')->onDelete('cascade');
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('sos_alerts');
    }
};
