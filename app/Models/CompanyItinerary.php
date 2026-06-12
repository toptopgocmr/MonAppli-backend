<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyItinerary extends Model
{
    use HasFactory;

    protected $table = 'company_itineraries';

    protected $fillable = [
        'company_id',
        'departure',
        'destination',
        'price',
        'distance_km',
        'duration_min',
        'vehicle_type',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'distance_km' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
