<?php

namespace App\Services\Realtime;

use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

/**
 * PusherBroadcaster — trigger Pusher direct, partagé par tous les
 * contrôleurs (appels, messages support...).
 *
 * Pourquoi pas Laravel Events (broadcast()) ? BROADCAST_DRIVER=log en
 * .env sur ce projet rend broadcast() silencieusement inopérant (rien
 * n'est jamais réellement envoyé à Pusher). Voir UserCallController /
 * DriverCallController qui suivent déjà ce même pattern pour les appels.
 */
class PusherBroadcaster
{
    public static function pusher(): Pusher
    {
        return new Pusher(
            env('PUSHER_APP_KEY',    'b936f5c8f1666939a7fa'),
            env('PUSHER_APP_SECRET', ''),
            env('PUSHER_APP_ID',     ''),
            ['cluster' => env('PUSHER_APP_CLUSTER', 'eu'), 'useTLS' => true]
        );
    }

    /**
     * Envoie un event Pusher, sans jamais lever d'exception (loggée en
     * warning) — un échec de notification temps réel ne doit jamais faire
     * planter la requête HTTP appelante.
     */
    public static function trigger(string $channel, string $event, array $payload): void
    {
        try {
            self::pusher()->trigger($channel, $event, $payload);
        } catch (\Throwable $e) {
            Log::warning("Pusher trigger error [{$channel}/{$event}]: " . $e->getMessage());
        }
    }
}
