<?php

// app/Models/Withdrawal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'driver_id', 'wallet_id', 'amount',
        'method', 'phone_number', 'status',
        'transaction_ref', 'processed_at'
    ];

    protected $dates = ['processed_at'];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver\Driver::class, 'driver_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

 }