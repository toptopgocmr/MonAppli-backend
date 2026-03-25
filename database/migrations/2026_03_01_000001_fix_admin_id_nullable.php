<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CORRECTIF : admin_id doit être nullable
     * car les messages peuvent venir de drivers aussi (pas forcément d'un admin)
     *
     * Lance : php artisan migrate
     */
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            // Rend admin_id nullable s'il existe
            if (Schema::hasColumn('support_messages', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->change();
            }

            // Sécurise aussi sender_id au cas où
            if (Schema::hasColumn('support_messages', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            if (Schema::hasColumn('support_messages', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable(false)->change();
            }
        });
    }
};