<?php

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosAlertSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SosAlert $alert) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.alert';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->alert->id,
            'sender_type' => class_basename($this->alert->sender_type),
            'sender_id'   => $this->alert->sender_id,
            'lat'         => $this->alert->lat,
            'lng'         => $this->alert->lng,
            'message'     => $this->alert->message,
            'trip_id'     => $this->alert->trip_id,
            'sent_at'     => $this->alert->created_at?->toDateTimeString(),
        ];
    }
}
