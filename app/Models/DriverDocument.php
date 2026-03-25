<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver\Driver;

class DriverDocument extends Model
{
    protected $fillable = [
        'driver_id', 'type', 'front_path', 'back_path',
        'number', 'issue_date', 'expiry_date',
        'issue_city', 'issue_country', 'status',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
