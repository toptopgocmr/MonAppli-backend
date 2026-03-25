<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement diffusé sur Pusher lors de l'envoi d'un message.
 *
 * Canaux écoutés :
 *   - trip.{trip_id}     → conversation entre client et chauffeur
 *   - user.{user_id}     → client (notification Flutter)
 *   - driver.{driver_id} → chauffeur (notification Flutter)
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['trip.driver', 'user']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('trip.' . $this->message->trip_id),
        ];

        // Notifier le client si le message vient du chauffeur
        if ($this->message->sender_type === 'App\\Models\\Driver') {
            $userId = $this->message->user_id
                ?? $this->message->trip?->user_id;
            if ($userId) {
                $channels[] = new Channel('user.' . $userId);
            }
        }

        // Notifier le chauffeur si le message vient du client
        if ($this->message->sender_type === 'App\\Models\\User') {
            $driverId = $this->message->trip?->driver_id;
            if ($driverId) {
                $channels[] = new Channel('driver.' . $driverId);
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $driver     = $this->message->trip?->driver;
        $driverName = $driver
            ? trim($driver->first_name . ' ' . $driver->last_name)
            : 'Chauffeur';

        return [
            'id'          => $this->message->id,
            'trip_id'     => $this->message->trip_id,
            'content'     => $this->message->content,
            'sender_type' => $this->message->sender_type,
            'driver_name' => $driverName,
            'created_at'  => $this->message->created_at?->toIso8601String(),
        ];
    }
}
