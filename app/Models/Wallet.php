<?php

// app/Models/Wallet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['driver_id', 'balance', 'currency'];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver\Driver::class, 'driver_id');
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}