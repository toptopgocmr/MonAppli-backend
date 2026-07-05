<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Driver\Driver;

class VehicleDriverShift extends Model
{
    use HasFactory;

    protected $table = 'vehicle_driver_shifts';

    protected $fillable = [
        'vehicle_id', 'driver_id',
        'day_of_week', 'specific_date',
        'start_time', 'end_time',
        'is_primary', 'status', 'notes',
    ];

    protected $casts = [
        'specific_date' => 'date:Y-m-d',
        'is_primary'    => 'boolean',
        'day_of_week'   => 'integer',
    ];

    const DAYS = [
        0 => 'Dimanche', 1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
        4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function getIsRecurringAttribute(): bool
    {
        return is_null($this->specific_date) && !is_null($this->day_of_week);
    }

    public function getDayLabelAttribute(): ?string
    {
        if (!is_null($this->specific_date)) {
            return $this->specific_date->locale('fr')->translatedFormat('d M Y');
        }
        return self::DAYS[$this->day_of_week] ?? null;
    }
}
