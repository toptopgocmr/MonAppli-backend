<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver\Driver;

class Vehicle extends Model
{
    protected $fillable = [
        'driver_id', 'plate', 'brand', 'model',
        'type', 'color', 'country', 'city',
        'lat', 'lng', 'status',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
