<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    protected $fillable = [
        'course_id', 'driver_id', 'user_id',
        'country_id', 'city_id', 'commission_rate_id',
        'montant_course', 'taux_applique', 'montant_commission',
        'currency', 'earned_at',
    ];

    protected $casts = [
        'montant_course'      => 'decimal:2',
        'taux_applique'       => 'decimal:2',
        'montant_commission'  => 'decimal:2',
        'earned_at'           => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function commissionRate(): BelongsTo
    {
        return $this->belongsTo(CommissionRate::class);
    }
}