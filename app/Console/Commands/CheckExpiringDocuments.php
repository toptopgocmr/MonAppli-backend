<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver\Driver;

class CheckExpiringDocuments extends Command
{
    protected $signature   = 'toptopgo:check-documents {--days=30}';
    protected $description = 'Vérifie les documents chauffeurs qui expirent bientôt';

    public function handle(): void
    {
        $days    = (int) $this->option('days');
        $drivers = Driver::where('status', 'approved')
            ->where(function ($q) use ($days) {
                $q->whereDate('id_card_expiry_date', '<=', now()->addDays($days))
                  ->orWhereDate('license_expiry_date', '<=', now()->addDays($days));
            })->get();

        $this->info("Documents expirant dans {$days} jours : {$drivers->count()} chauffeur(s).");

        foreach ($drivers as $driver) {
            $this->line(" - [{$driver->id}] {$driver->first_name} {$driver->last_name} | CIN expire: {$driver->id_card_expiry_date} | Permis expire: {$driver->license_expiry_date}");
        }

        $this->info('Vérification terminée.');
    }
}
