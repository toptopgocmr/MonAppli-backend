<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Les appels vers le support ou vers une société ne sont pas toujours
 * rattachés à un trajet précis (ex: "appeler le support" depuis l'app,
 * en dehors de tout trajet en cours). La colonne trip_id de `calls` était
 * NOT NULL avec contrainte FK — on l'assouplit ici sans casser les appels
 * client↔chauffeur existants (qui continuent de renseigner trip_id).
 *
 * Utilise du SQL brut (pas de ->change()) car doctrine/dbal n'est pas
 * installé sur ce projet — ->change() lèverait une exception fatale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('calls') || !Schema::hasColumn('calls', 'trip_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            Log::warning('Migration make_calls_trip_id_nullable ignorée : driver non-MySQL.');
            return;
        }

        try {
            DB::statement('ALTER TABLE `calls` DROP FOREIGN KEY `calls_trip_id_foreign`');
        } catch (\Throwable $e) {
            Log::warning('drop FK calls_trip_id_foreign: ' . $e->getMessage());
        }

        DB::statement('ALTER TABLE `calls` MODIFY `trip_id` BIGINT UNSIGNED NULL');

        try {
            DB::statement('ALTER TABLE `calls` ADD CONSTRAINT `calls_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            Log::warning('add FK calls_trip_id_foreign: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('calls') || !Schema::hasColumn('calls', 'trip_id')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `calls` DROP FOREIGN KEY `calls_trip_id_foreign`');
        } catch (\Throwable $e) {
            Log::warning('drop FK calls_trip_id_foreign: ' . $e->getMessage());
        }

        DB::statement('DELETE FROM `calls` WHERE `trip_id` IS NULL');
        DB::statement('ALTER TABLE `calls` MODIFY `trip_id` BIGINT UNSIGNED NOT NULL');

        try {
            DB::statement('ALTER TABLE `calls` ADD CONSTRAINT `calls_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            Log::warning('add FK calls_trip_id_foreign: ' . $e->getMessage());
        }
    }
};
