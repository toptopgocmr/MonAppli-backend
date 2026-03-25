<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Corriger les données invalides avant de modifier l'ENUM
        DB::statement("UPDATE drivers SET status = 'approved' WHERE status IN ('online', 'pause', 'offline')");

        // Maintenant modifier l'ENUM proprement
        DB::statement("ALTER TABLE drivers MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void {}
};