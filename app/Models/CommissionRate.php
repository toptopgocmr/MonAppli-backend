<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver\Driver;

class CommissionRate extends Model
{
    protected $fillable = [
        'type', 'country', 'vehicle_type', 'driver_id',
        'rate', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate'      => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Résoudre le taux applicable pour un trip donné.
     * Priorité : driver > vehicle_type > country > global
     */
    public static function resolveRate(
        int $driverId,
        string $vehicleType,
        string $country
    ): float {
        $rules = self::where('is_active', true)->get();

        // 1. Taux spécifique au chauffeur
        $driverRule = $rules->where('type', 'driver')->where('driver_id', $driverId)->first();
        if ($driverRule) return $driverRule->rate;

        // 2. Taux par type de véhicule
        $vehicleRule = $rules->where('type', 'vehicle_type')->where('vehicle_type', $vehicleType)->first();
        if ($vehicleRule) return $vehicleRule->rate;

        // 3. Taux par pays
        $countryRule = $rules->where('type', 'country')->where('country', $country)->first();
        if ($countryRule) return $countryRule->rate;

        // 4. Taux global par défaut
        $globalRule = $rules->where('type', 'global')->first();
        return $globalRule?->rate ?? 10.00;
    }

    /**
     * Label lisible pour le type
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'global'       => '🌐 Global',
            'country'      => '🌍 Pays : ' . $this->country,
            'vehicle_type' => '🚗 Véhicule : ' . $this->vehicle_type,
            'driver'       => '👤 Chauffeur : ' . ($this->driver?->first_name . ' ' . $this->driver?->last_name),
            default        => $this->type,
        };
    }
}