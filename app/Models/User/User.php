<?php

namespace App\Models\User;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\SupportMessage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'phone', 'email',
        'country', 'city', 'password', 'status',
    ];

    protected $hidden = ['password'];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function supportMessages()
    {
        return $this->morphMany(SupportMessage::class, 'recipient');
    }
}