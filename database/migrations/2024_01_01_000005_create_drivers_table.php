<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->string('birth_place');
            $table->string('country_birth');
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // Documents
            $table->string('id_card_front')->nullable();
            $table->string('id_card_back')->nullable();
            $table->string('license_front')->nullable();
            $table->string('license_back')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->string('insurance')->nullable();
            $table->date('id_card_issue_date')->nullable();
            $table->date('id_card_expiry_date')->nullable();
            $table->string('id_card_issue_city')->nullable();
            $table->string('id_card_issue_country')->nullable();
            $table->date('license_issue_date')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_issue_city')->nullable();
            $table->string('license_issue_country')->nullable();

            // Véhicule
            $table->string('vehicle_plate')->nullable()->unique();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->enum('vehicle_type', ['Standard', 'Confort', 'Van', 'PMR'])->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('vehicle_country')->nullable();
            $table->string('vehicle_city')->nullable();
            $table->decimal('vehicle_lat', 10, 7)->nullable();
            $table->decimal('vehicle_lng', 10, 7)->nullable();

            $table->enum('driver_status', ['online', 'pause', 'offline'])->default('offline');
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
