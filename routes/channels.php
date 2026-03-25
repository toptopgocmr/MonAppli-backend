<?php

use App\Models\User;
use App\Models\Ride;
use Illuminate\Support\Facades\Broadcast;

// Channel privé pour support messages
Broadcast::channel('support.{recipientId}', function ($user, $recipientId) {
    return $user->id == $recipientId;
});

// Channels existants
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

Broadcast::channel('ride.{rideId}', function (User $user, int $rideId) {
    $ride = Ride::find($rideId);
    return $ride && ($user->id === $ride->passenger_id || $user->id === $ride->driver_id);
});

Broadcast::channel('drivers.available', function (User $user) {
    if (!$user->isDriver()) return false;
    $profile = $user->driverProfile;
    if (!$profile || !$profile->is_online) return false;
    return [
        'id' => $user->id,
        'name' => $user->full_name,
        'location' => [
            'latitude' => $profile->current_latitude,
            'longitude' => $profile->current_longitude,
        ],
    ];
});

Broadcast::channel('rides', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('admin.dashboard', function (User $user) {
    return $user->isAdmin();
});