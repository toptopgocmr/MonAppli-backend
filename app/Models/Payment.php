<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;
use App\Models\Driver\Driver;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'trip_id', 'driver_id', 'booking_id',
        'amount', 'commission', 'driver_net',
        'method', 'provider', 'status', 'transaction_ref',
        'flw_charge_id', 'provider_transaction_id', 'receipt_number',
        'country', 'city', 'paid_at', 'failure_reason',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'float',
    ];

    const METHOD_MOBILE_MONEY = 'mobile_money';
    const METHOD_STRIPE       = 'stripe';
    const METHOD_CASH         = 'cash';

    const STATUS_PENDING   = 'pending';
    const STATUS_SUCCESS   = 'success';
    const STATUS_FAILED    = 'failed';
    const STATUS_REFUNDED  = 'refunded';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}