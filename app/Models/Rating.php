<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'booking_id', 'driver_id', 'user_id',
        'driver_rating', 'user_rating',
        'driver_comment', 'user_comment',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function driver()  { return $this->belongsTo(\App\Models\Driver\Driver::class); }
    public function user()    { return $this->belongsTo(\App\Models\User\User::class); }
}
