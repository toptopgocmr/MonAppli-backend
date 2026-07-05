<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Driver\Driver;

class Vehicle extends Model
{
    use HasFactory;

    // Types de véhicule disponibles dans la flotte société.
    // Standard/Confort/Van/PMR existaient déjà (synchronisés vers l'enum
    // drivers.vehicle_type) ; les autres sont proposés en complément pour
    // couvrir les besoins des sociétés de transport (minibus, bus, etc.).
    public const TYPES = [
        'Standard', 'Confort', 'Van', 'PMR', 'Minibus', 'Bus', 'Pickup', 'SUV', 'Moto', 'Utilitaire',
    ];

    protected $fillable = [
        'company_id', 'plate', 'brand', 'model',
        'type', 'color', 'country', 'city',
        'lat', 'lng', 'status', 'notes',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function shifts()
    {
        return $this->hasMany(VehicleDriverShift::class);
    }

    public function activeShifts()
    {
        return $this->hasMany(VehicleDriverShift::class)->where('status', 'active');
    }

    // Chauffeurs actuellement associés à ce véhicule (via les créneaux actifs)
    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'vehicle_driver_shifts')
                    ->wherePivot('status', 'active')
                    ->withPivot(['id', 'day_of_week', 'specific_date', 'start_time', 'end_time', 'is_primary', 'status'])
                    ->distinct();
    }
}
