<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver\Driver;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id', 'lat', 'lng',
        'driver_status', 'recorded_at',
    ];

    public function driver() { return $this->belongsTo(Driver::class); }
}
