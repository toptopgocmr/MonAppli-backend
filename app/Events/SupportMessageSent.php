<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public SupportMessage $message) {}

    public function broadcastOn(): Channel
    {
        // ✅ Canal fixe que Flutter écoute déjà
        return new Channel('admin-support');
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->message->id,
            'content'        => $this->message->content,
            'sender_type'    => $this->message->sender_type,
            'sender_id'      => $this->message->sender_id,
            'recipient_type' => $this->message->recipient_type,
            'recipient_id'   => $this->message->recipient_id,
            'created_at'     => $this->message->created_at->format('d/m H:i'),
        ];
    }
}