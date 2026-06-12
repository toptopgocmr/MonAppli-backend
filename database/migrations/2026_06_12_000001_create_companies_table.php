<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->enum('type', ['location', 'covoiturage', 'both'])->default('both');
            $table->enum('status', ['active', 'suspended', 'pending'])->default('pending');
            $table->string('contact_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // % commission plateforme
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
