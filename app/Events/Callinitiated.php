<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CallInitiated — Déclenche la bannière "Appel entrant" sur Flutter
 *
 * Canaux Pusher écoutés :
 *   trip.{trip_id}      → les deux parties (client + chauffeur)
 *   user.{user_id}      → bannière Flutter app client
 *   driver.{driver_id}  → bannière Flutter app chauffeur
 */
class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Call $call;

    public function __construct(Call $call)
    {
        // Charger les relations nécessaires pour broadcastWith()
        $this->call = $call->load(['trip.driver', 'trip.user']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('trip.' . $this->call->trip_id),
        ];

        $trip = $this->call->trip;

        // Si appelant = chauffeur → notifier le client
        if (str_contains($this->call->caller_type, 'Driver')) {
            if ($trip?->user_id) {
                $channels[] = new Channel('user.' . $trip->user_id);
            }
        }

        // Si appelant = client → notifier le chauffeur
        if (str_contains($this->call->caller_type, 'User')) {
            if ($trip?->driver_id) {
                $channels[] = new Channel('driver.' . $trip->driver_id);
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'call.incoming';
    }

    public function broadcastWith(): array
    {
        $trip   = $this->call->trip;
        $driver = $trip?->driver;
        $user   = $trip?->user;

        // Infos de l'appelant
        if (str_contains($this->call->caller_type, 'Driver')) {
            $callerName  = $driver ? trim($driver->first_name . ' ' . $driver->last_name) : 'Chauffeur';
            $callerPhoto = $driver?->profile_photo ?? '';
        } else {
            $callerName  = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Client';
            $callerPhoto = $user?->profile_photo ?? '';
        }

        return [
            'call_id'      => $this->call->id,
            'trip_id'      => $this->call->trip_id,
            'caller_id'    => $this->call->caller_id,
            'caller_type'  => $this->call->caller_type,
            'caller_name'  => $callerName,
            'caller_photo' => $callerPhoto,
            'call_type'    => $this->call->type,
            'status'       => $this->call->status,
            'initiated_at' => $this->call->created_at?->toIso8601String(),
        ];
    }
}