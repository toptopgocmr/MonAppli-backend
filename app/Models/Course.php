<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    protected $fillable = [
        'user_id', 'driver_id', 'country_id', 'city_id',
        'pickup_address', 'dropoff_address',
        'pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng',
        'distance_km', 'montant_total', 'currency',
        'status', 'started_at', 'completed_at', 'cancel_reason',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'distance_km'   => 'decimal:2',
        'pickup_lat'    => 'decimal:7',
        'pickup_lng'    => 'decimal:7',
        'dropoff_lat'   => 'decimal:7',
        'dropoff_lng'   => 'decimal:7',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}