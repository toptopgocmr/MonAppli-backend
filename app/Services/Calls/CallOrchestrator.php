<?php

namespace App\Services\Calls;

use App\Models\Call;
use App\Services\Agora\AgoraTokenService as Agora;
use App\Services\Realtime\PusherBroadcaster;

/**
 * CallOrchestrator — logique commune à TOUS les appels vocaux in-app qui
 * ne sont pas le face-à-face client↔chauffeur historique (celui-ci reste
 * géré par UserCallController/DriverCallController, inchangés).
 *
 * Couvre : client↔support, chauffeur↔support, client↔société,
 * société↔support, support→(client|chauffeur|société).
 *
 * Chaque "partie" est identifiée par son couple (type, id) où type est la
 * classe Eloquent polymorphique (User::class, Driver::class, Company::class,
 * AdminUser::class). Le support n'a pas d'agent précis assigné : n'importe
 * quel admin connecté au panel peut décrocher (file d'attente partagée sur
 * le channel Pusher "admin-support").
 */
class CallOrchestrator
{
    /**
     * Nombre max d'appels simultanés, PAR CATÉGORIE d'appelant (client,
     * chauffeur, société), vers le support — comme des "lignes" de call
     * center. Évite qu'une seule catégorie ne monopolise toute la file, et
     * donne un message clair ("ligne occupée") plutôt qu'un blocage muet
     * une fois la capacité atteinte. N'importe quel admin connecté peut
     * décrocher n'importe laquelle de ces lignes (file partagée).
     */
    const SUPPORT_CAPACITY_PER_TYPE = 10;

    /**
     * Initie un appel entre deux parties.
     * Retourne [Call|null, agoraPourAppelant|null, alreadyActive, busy].
     */
    public function initiate(
        string $callerType,
        int $callerId,
        string $callerName,
        string $callerPhoto,
        string $receiverType,
        int $receiverId,
        ?int $tripId = null,
        string $type = 'audio'
    ): array {
        // ── Capacité "call center" support ──────────────────────────────
        if ($receiverType === \App\Models\Admin\AdminUser::class) {
            $activeForType = Call::where('caller_type', $callerType)
                ->where('receiver_type', \App\Models\Admin\AdminUser::class)
                ->active()
                ->count();

            if ($activeForType >= self::SUPPORT_CAPACITY_PER_TYPE) {
                return [null, null, false, true]; // true = toutes les lignes occupées
            }
        }

        $active = Call::betweenParties($callerType, $callerId, $receiverType, $receiverId)
            ->active()
            ->first();

        // ✅ Un appel "actif" abandonné (personne n'a décroché, onglet fermé
        // sans raccrocher…) ne doit jamais bloquer indéfiniment les appels
        // suivants entre les deux mêmes parties.
        if ($active && $active->isStale()) {
            $active->update(['status' => 'missed', 'ended_at' => now()]);
            $active = null;
        }

        if ($active) {
            return [$active, null, true, false]; // true = déjà un appel actif
        }

        $call = Call::create([
            'trip_id'       => $tripId,
            'caller_type'   => $callerType,
            'caller_id'     => $callerId,
            'receiver_type' => $receiverType,
            'receiver_id'   => $receiverId,
            'type'          => $type,
            'status'        => 'initiated',
            'started_at'    => now(),
        ]);

        PusherBroadcaster::trigger(
            Agora::pusherChannelFor($receiverType, $receiverId),
            'call.incoming',
            [
                'trip_id'      => $tripId,
                'call_id'      => $call->id,
                'caller_type'  => $callerType,
                'caller_id'    => $callerId,
                'caller_name'  => $callerName,
                'caller_photo' => $callerPhoto,
                'queue_type'   => self::queueTypeFor($callerType),
                'initiated_at' => now()->toIso8601String(),
            ]
        );

        $agora = Agora::generate(Agora::channelForCall($call->id), Agora::uidFor($callerType, $callerId));

        return [$call, $agora, false, false];
    }

    /**
     * Catégorie affichée côté widget support (client / chauffeur / société),
     * pour regrouper visuellement la file d'attente par type d'appelant.
     */
    public static function queueTypeFor(string $callerType): ?string
    {
        return match ($callerType) {
            \App\Models\User\User::class       => 'client',
            \App\Models\Driver\Driver::class    => 'chauffeur',
            \App\Models\Company::class          => 'societe',
            default => null,
        };
    }

    /**
     * L'un des deux participants (celui identifié par $selfType/$selfId)
     * décroche — génère son propre token Agora pour le canal de cet appel.
     */
    public function answer(int $callId, string $selfType, int $selfId): ?array
    {
        $call = Call::find($callId);
        if (!$call || !$this->isParticipant($call, $selfType, $selfId)) {
            return null;
        }

        if ($call->status === 'initiated') {
            // ✅ Mise à jour atomique conditionnelle : si deux admins cliquent
            // "Répondre" en même temps sur le même appel de la file partagée,
            // un seul des deux gagne la course (0 ligne affectée = déjà pris).
            $won = Call::where('id', $callId)->where('status', 'initiated')
                ->update(['status' => 'answered', 'started_at' => now()]);

            if (!$won) {
                return ['call' => $call->fresh(), 'agora' => null, 'already_taken' => true];
            }

            $call->refresh();

            // File d'attente support partagée : prévenir les AUTRES admins
            // connectés que cet appel vient d'être pris, pour qu'ils le
            // retirent de leur propre liste d'appels en attente.
            if ($call->receiver_type === \App\Models\Admin\AdminUser::class) {
                PusherBroadcaster::trigger('admin-support', 'call.taken', ['call_id' => $call->id]);
            }
        }

        return [
            'call'  => $call,
            'agora' => Agora::generate(Agora::channelForCall($call->id), Agora::uidFor($selfType, $selfId)),
        ];
    }

    /**
     * Régénère/renvoie le token Agora pour un participant déjà engagé dans
     * l'appel (ex: reconnexion, ou récupération après notification reçue).
     */
    public function token(int $callId, string $selfType, int $selfId): ?array
    {
        $call = Call::find($callId);
        if (!$call || !$this->isParticipant($call, $selfType, $selfId)) {
            return null;
        }

        return [
            'call'  => $call,
            'agora' => Agora::generate(Agora::channelForCall($call->id), Agora::uidFor($selfType, $selfId)),
        ];
    }

    public function end(int $callId): ?Call
    {
        $call = Call::find($callId);
        if (!$call) return null;

        // ✅ abs() : selon la version de Carbon, diffInSeconds() n'est plus
        // toujours absolu par défaut — sans ça la durée affichée pouvait
        // être négative (ex: "-1:-22" dans le journal des appels).
        $duration = $call->started_at ? (int) abs(now()->diffInSeconds($call->started_at)) : 0;
        $call->update(['status' => 'ended', 'duration_seconds' => $duration, 'ended_at' => now()]);

        // Prévenir l'AUTRE partie (celle qui n'a pas raccroché) que l'appel est terminé.
        $this->notifyOtherParty($call, 'call.ended', ['duration' => $duration]);

        return $call;
    }

    public function missed(int $callId): ?Call
    {
        $call = Call::find($callId);
        if (!$call) return null;

        $call->update(['status' => 'missed', 'ended_at' => now()]);
        $this->notifyOtherParty($call, 'call.ended', ['missed' => true]);

        return $call;
    }

    private function isParticipant(Call $call, string $type, int $id): bool
    {
        return ($call->caller_type === $type && (int) $call->caller_id === $id)
            || ($call->receiver_type === $type && (int) $call->receiver_id === $id);
    }

    /**
     * Notifie la partie qui n'a PAS déclenché l'action courante. On ne sait
     * pas ici qui a raccroché en premier, donc on notifie les DEUX channels
     * (caller + receiver) — sans effet néfaste : celui qui a lui-même
     * raccroché ignore simplement l'event côté client.
     */
    private function notifyOtherParty(Call $call, string $event, array $extra = []): void
    {
        $payload = array_merge([
            'call_id' => $call->id,
            'trip_id' => $call->trip_id,
        ], $extra);

        PusherBroadcaster::trigger(
            Agora::pusherChannelFor($call->caller_type, (int) $call->caller_id),
            $event,
            $payload
        );
        PusherBroadcaster::trigger(
            Agora::pusherChannelFor($call->receiver_type, (int) $call->receiver_id),
            $event,
            $payload
        );
    }
}
