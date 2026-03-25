<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Driver\Driver;

/**
 * MessageResource — Formatage unifié des messages pour les apps Flutter.
 *
 * Utilisé par DriverMessageController::show() et store()
 * Compatible avec la structure attendue par chat_conversation_page.dart
 */
class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        // Détermine si le message vient du chauffeur ou du client
        $isDriver = str_contains($this->sender_type ?? '', 'Driver');

        return [
            'id'          => $this->id,
            'trip_id'     => $this->trip_id,
            'content'     => $this->content,
            'sender'      => $isDriver ? 'driver' : 'client',
            'sender_type' => $this->sender_type,
            'sender_id'   => $this->sender_id,
            'is_read'     => (bool) $this->is_read,
            'refused'     => (bool) ($this->refused ?? false),
            'read_at'     => $this->read_at?->toIso8601String(),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}