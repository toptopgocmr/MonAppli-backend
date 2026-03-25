<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CallEnded — Notifie les deux parties quand un appel se termine.
 * Statuts possibles : 'ended' | 'missed' | 'answered'
 */
class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Call $call) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('trip.' . $this->call->trip_id)];
        $trip     = $this->call->trip;

        if ($trip?->user_id)   $channels[] = new Channel('user.'   . $trip->user_id);
        if ($trip?->driver_id) $channels[] = new Channel('driver.' . $trip->driver_id);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'          => $this->call->id,
            'trip_id'          => $this->call->trip_id,
            'status'           => $this->call->status,
            'duration_seconds' => $this->call->duration_seconds,
            'ended_at'         => $this->call->ended_at?->toIso8601String(),
        ];
    }
}