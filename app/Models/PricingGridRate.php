<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingGridRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_grid_id', 'label', 'vehicle_type', 'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function grid()
    {
        return $this->belongsTo(PricingGrid::class, 'pricing_grid_id');
    }
}
