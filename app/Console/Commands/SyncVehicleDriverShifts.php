<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleDriverShift;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncVehicleDriverShifts extends Command
{
    protected $signature = 'toptopgo:sync-vehicle-shifts';

    protected $description = "Bascule automatique : synchronise sur chaque chauffeur les infos du véhicule qu'il conduit actuellement, selon le planning (créneaux récurrents ou dates précises)";

    private const ALLOWED_TYPES = ['Standard', 'Confort', 'Van', 'PMR'];

    public function handle(): int
    {
        $now      = now();
        $today    = $now->toDateString();
        $nowTime  = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeek; // 0=Dimanche ... 6=Samedi (identique à Carbon)

        $vehicles = Vehicle::with(['shifts' => fn ($q) => $q->where('status', 'active')->with('driver')])->get();

        $updated = 0;

        foreach ($vehicles as $vehicle) {
            $activeShift = $this->resolveActiveShift($vehicle->shifts, $today, $nowTime, $dayOfWeek);

            if (!$activeShift || !$activeShift->driver) {
                continue;
            }

            $this->syncVehicleToDriver($vehicle, $activeShift->driver);
            $updated++;
        }

        $this->info("Synchronisation terminée : {$updated} chauffeur(s) mis à jour sur " . $vehicles->count() . ' véhicule(s).');

        return Command::SUCCESS;
    }

    /**
     * Détermine le créneau actif pour CE véhicule à l'instant présent.
     * Priorité : date précise (aujourd'hui) > créneau récurrent > chauffeur principal en dernier recours.
     */
    private function resolveActiveShift($shifts, string $today, string $nowTime, int $dayOfWeek): ?VehicleDriverShift
    {
        // 1) Date précise aujourd'hui, dans la plage horaire
        $specific = $shifts->filter(function ($s) use ($today, $nowTime) {
            return $s->specific_date && $s->specific_date->format('Y-m-d') === $today
                && $this->withinRange($nowTime, $s->start_time, $s->end_time);
        });
        if ($specific->isNotEmpty()) {
            return $specific->firstWhere('is_primary', true) ?? $specific->first();
        }

        // 2) Créneau récurrent du jour, dans la plage horaire
        $recurring = $shifts->filter(function ($s) use ($dayOfWeek, $nowTime) {
            return is_null($s->specific_date) && (int) $s->day_of_week === $dayOfWeek
                && $this->withinRange($nowTime, $s->start_time, $s->end_time);
        });
        if ($recurring->isNotEmpty()) {
            return $recurring->firstWhere('is_primary', true) ?? $recurring->first();
        }

        return null;
    }

    /**
     * true si $nowTime est dans [$start, $end], en gérant les créneaux
     * de nuit qui traversent minuit (ex: 22:00 → 06:00).
     * Si $start ou $end sont vides, le créneau est considéré actif toute la journée.
     */
    private function withinRange(string $nowTime, ?string $start, ?string $end): bool
    {
        if (!$start || !$end) {
            return true;
        }

        $start = Carbon::parse($start)->format('H:i:s');
        $end   = Carbon::parse($end)->format('H:i:s');

        if ($start <= $end) {
            return $nowTime >= $start && $nowTime <= $end;
        }

        // Créneau de nuit (traverse minuit)
        return $nowTime >= $start || $nowTime <= $end;
    }

    private function syncVehicleToDriver(Vehicle $vehicle, $driver): void
    {
        // Rien à faire si déjà à jour (évite des writes inutiles)
        if ($driver->vehicle_plate === $vehicle->plate
            && $driver->vehicle_brand === $vehicle->brand
            && $driver->vehicle_model === $vehicle->model) {
            return;
        }

        $driver->update([
            'vehicle_plate' => $vehicle->plate,
            'vehicle_brand' => $vehicle->brand,
            'vehicle_model' => $vehicle->model,
            'vehicle_color' => $vehicle->color,
            'vehicle_type'  => in_array($vehicle->type, self::ALLOWED_TYPES, true) ? $vehicle->type : $driver->vehicle_type,
            'vehicle_city'  => $vehicle->city ?? $driver->vehicle_city,
            'vehicle_country' => $vehicle->country ?? $driver->vehicle_country,
        ]);
    }
}
