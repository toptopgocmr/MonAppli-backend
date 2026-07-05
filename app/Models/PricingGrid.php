<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingGrid extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function rates()
    {
        return $this->hasMany(PricingGridRate::class);
    }

    public function itineraries()
    {
        return $this->hasMany(CompanyItinerary::class);
    }
}
