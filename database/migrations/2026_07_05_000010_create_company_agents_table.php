<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Comptes secondaires d'une société (comptable, RH, directeur général,
// responsable flotte, marketing & communication, commercial...) : chacun se
// connecte avec ses propres identifiants et n'accède qu'aux pages du panel
// société pour lesquelles il a une permission.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_agents')) {
            Schema::create('company_agents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->string('role'); // comptable, rh, directeur_general, flotte, marketing_communication, commercial, autre
                // Permissions : tableau JSON de clés (ex: ["drivers","vehicles","withdrawals"]).
                $table->json('permissions')->nullable();
                $table->enum('status', ['active', 'suspended'])->default('active');
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_agents');
    }
};
