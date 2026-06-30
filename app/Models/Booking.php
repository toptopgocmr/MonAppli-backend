<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'seats',
        'passengers',
        'amount',
        'status',
        'booked_at',
        'cancellation_reason',
        'cancelled_at',
        'boarded_at',
    ];

    protected $casts = [
        'booked_at'    => 'datetime',
        'cancelled_at' => 'datetime',
        'boarded_at'   => 'datetime',
        'amount'       => 'float',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAID      = 'paid';
    const STATUS_COMPLETED = 'completed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
