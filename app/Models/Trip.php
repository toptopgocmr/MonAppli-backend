<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Driver\Driver;
use App\Models\User\User;
use App\Models\Message; // relation messages

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id', 'user_id',
        'departure', 'pickup_address', 'pickup_point', 'departure_city',
        'pickup_lat', 'pickup_lng',
        'destination', 'dropoff_address', 'dropoff_point', 'destination_city',
        'dropoff_lat', 'dropoff_lng',
        'departure_date', 'departure_time',
        'price_per_seat', 'amount', 'commission', 'driver_net',
        'available_seats', 'total_seats',
        'luggage_included', 'luggage_kg', 'luggage_weight_kg',
        'extra_luggage_fee', 'extra_luggage_slots',
        'vehicle_type', 'distance_km',
        'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'departure_date'      => 'date:Y-m-d',
        'price_per_seat'      => 'float',
        'amount'              => 'float',
        'commission'          => 'float',
        'driver_net'          => 'float',
        'available_seats'     => 'integer',
        'luggage_included'    => 'integer',
        'luggage_weight_kg'   => 'float',
        'extra_luggage_fee'   => 'float',
        'extra_luggage_slots' => 'integer',
        'pickup_lat'          => 'float',
        'pickup_lng'          => 'float',
        'dropoff_lat'         => 'float',
        'dropoff_lng'         => 'float',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function driver()
    {
        return $this->belongsTo(Driver::class)->withDefault([
            'first_name'    => 'Chauffeur',
            'last_name'     => '',
            'phone'         => '',
            'profile_photo' => null,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'first_name' => 'Client',
            'last_name'  => '',
            'phone'      => '',
        ]);
    }

    // Alias attendu par Admin\TripController — véhicule porté par le Driver
    public function vehicle()
    {
        return $this->belongsTo(Driver::class, 'driver_id')->withDefault([
            'vehicle_type'  => '',
            'vehicle_brand' => '',
            'vehicle_model' => '',
            'vehicle_color' => '',
            'vehicle_plate' => '',
        ]);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // relation messages client ↔ chauffeur
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getConfirmedSeatsAttribute(): int
    {
        return (int) $this->bookings()
            ->whereIn('status', ['confirmed', 'paid'])
            ->sum('seats');
    }
}