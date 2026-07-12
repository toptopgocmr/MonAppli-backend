<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallRecording;
use App\Models\Trip;
use App\Services\Agora\AgoraCloudRecordingService;
use App\Services\Agora\AgoraTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

/**
 * DriverCallController — Appels voix in-app côté Chauffeur
 *
 * Routes (dans api.php) :
 *   POST  /api/driver/calls/{trip_id}/initiate  → appeler le client
 *   POST  /api/driver/calls/{call_id}/answer    → décrocher
 *   POST  /api/driver/calls/{call_id}/end       → raccrocher
 *   POST  /api/driver/calls/{call_id}/missed    → marquer manqué
 *   GET   /api/driver/calls/{trip_id}           → historique
 *
 * NOTE : broadcast() diffusion via les Events Laravel a été volontairement
 * évité ici — le driver de broadcasting configuré (BROADCAST_DRIVER) vaut
 * "log" par défaut sur ce projet (voir .env), ce qui rendrait broadcast()
 * silencieusement inopérant. On utilise donc le même pattern que
 * UserCallController : un trigger Pusher direct, garanti fonctionnel.
 */
class DriverCallController extends Controller
{
    private function pusher(): Pusher
    {
        return new Pusher(
            env('PUSHER_APP_KEY',    'b936f5c8f1666939a7fa'),
            env('PUSHER_APP_SECRET', ''),
            env('PUSHER_APP_ID',     ''),
            ['cluster' => env('PUSHER_APP_CLUSTER', 'eu'), 'useTLS' => true]
        );
    }

    /**
     * Initier un appel vers le client.
     * → Déclenche IncomingCallBanner sur l'app client via Pusher.
     */
    public function initiate(Request $request, $tripId): JsonResponse
    {
        $driver = $request->user();

        $trip = Trip::where('id', $tripId)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet introuvable ou non autorisé.',
            ], 404);
        }

        // Vérifier qu'il n'y a pas déjà un appel actif sur ce trajet — sauf
        // s'il est "abandonné" (personne n'a décroché, app fermée sans
        // raccrocher), auquel cas on le referme automatiquement.
        $active = Call::forTrip($tripId)->active()->first();
        if ($active && $active->isStale()) {
            $active->update(['status' => 'missed', 'ended_at' => now()]);
            $active = null;
        }
        if ($active) {
            return response()->json([
                'success' => false,
                'message' => 'Un appel est déjà en cours sur ce trajet.',
                'call_id' => $active->id,
            ], 409);
        }

        $call = Call::create([
            'trip_id'       => $tripId,
            'caller_type'   => get_class($driver),          // App\Models\Driver\Driver
            'caller_id'     => $driver->id,
            'receiver_type' => \App\Models\User\User::class, // App\Models\User\User
            'receiver_id'   => $trip->user_id,
            'type'          => $request->input('type', 'audio'),
            'status'        => 'initiated',
            'started_at'    => now(),
        ]);

        // 📡 Pusher → channel personnel du client (user.{id}), event
        // call.incoming — c'est exactement ce que PusherService._subscribeUser()
        // écoute côté app Client (mobile-client-main/lib/core/services/pusher_service.dart).
        try {
            $callerName  = trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? ''));
            $callerPhoto = '';
            if (!empty($driver->profile_photo)) {
                $callerPhoto = str_starts_with($driver->profile_photo, 'http')
                    ? $driver->profile_photo
                    : asset('storage/' . $driver->profile_photo);
            }

            $this->pusher()->trigger("user.{$trip->user_id}", 'call.incoming', [
                'trip_id'      => (int) $tripId,
                'call_id'      => $call->id,
                'caller_id'    => $driver->id,
                'caller_name'  => $callerName ?: 'Votre chauffeur',
                'caller_photo' => $callerPhoto,
                'initiated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Pusher call.incoming error: ' . $e->getMessage());
        }

        Log::info('📞 Appel initié par chauffeur', [
            'call_id'   => $call->id,
            'driver_id' => $driver->id,
            'trip_id'   => $tripId,
            'user_id'   => $trip->user_id,
        ]);

        // ✅ Audio réel (Agora) : le chauffeur qui initie l'appel rejoint tout
        // de suite le canal dédié. Le client récupère le sien via
        // GET /user/calls/{callId}/token une fois la notification reçue.
        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForDriver($driver->id));

        return response()->json([
            'success' => true,
            'message' => 'Appel initié. En attente de réponse du client.',
            'call'    => [
                'id'      => $call->id,
                'trip_id' => $call->trip_id,
                'type'    => $call->type,
                'status'  => $call->status,
            ],
            'agora' => $agora,
        ]);
    }

    /**
     * GET /driver/calls/{callId}/token
     * Récupère (ou régénère) le token Agora du chauffeur pour un appel en
     * cours, qu'il en soit l'appelant ou le destinataire (appel client).
     */
    public function token(Request $request, $callId): JsonResponse
    {
        $driver = $request->user();
        $call   = Call::find($callId);

        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $driverClass = \App\Models\Driver\Driver::class;
        $isParticipant =
            ($call->caller_type === $driverClass && (int) $call->caller_id === (int) $driver->id) ||
            ($call->receiver_type === $driverClass && (int) $call->receiver_id === (int) $driver->id);

        if (!$isParticipant) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForDriver($driver->id));

        if (!$agora) {
            return response()->json([
                'success' => false,
                'message' => "Le service d'appel audio n'est pas configuré.",
            ], 503);
        }

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * Décrocher un appel entrant (le client a appelé le chauffeur).
     */
    public function answer(Request $request, $callId): JsonResponse
    {
        $driver = $request->user();
        $call   = Call::with('trip')->find($callId);

        if (!$call || $call->status !== 'initiated') {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $call->update(['status' => 'answered', 'started_at' => now()]);

        // ✅ Le chauffeur (destinataire d'un appel initié par le client) reçoit
        // ici son token Agora pour rejoindre le même canal que l'appelant.
        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForDriver($driver->id));

        // 🎙️ Démarrage de l'enregistrement Cloud Recording : à ce stade les
        // DEUX parties sont dans le canal (l'appelant l'a rejoint dès
        // initiate(), le destinataire vient de recevoir son token ci-dessus).
        // Best-effort : ne bloque jamais la réponse à l'appel si Agora Cloud
        // Recording n'est pas configuré ou échoue.
        if (!$call->recording_resource_id) {
            $recording = AgoraCloudRecordingService::start($call->id, $channel);
            if ($recording) {
                $call->update([
                    'recording_resource_id' => $recording['resourceId'],
                    'recording_sid'         => $recording['sid'],
                    'recording_uid'         => $recording['uid'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Appel décroché.', 'agora' => $agora]);
    }

    /**
     * Raccrocher (terminer l'appel).
     */
    public function end(Request $request, $callId): JsonResponse
    {
        $call = Call::with('trip')->find($callId);

        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        // ✅ abs() : selon la version de Carbon, diffInSeconds() n'est plus
        // toujours absolu par défaut — sans ça la durée affichée pouvait
        // être négative.
        $duration = $call->started_at
            ? (int) abs(now()->diffInSeconds($call->started_at))
            : 0;

        $call->update([
            'status'           => 'ended',
            'duration_seconds' => $duration,
            'ended_at'         => now(),
        ]);

        $this->stopCloudRecording($call);

        // ✅ FIX : "user.{$call->receiver_id}" supposait à tort que le client
        // est toujours receiver — faux si c'est LUI qui avait initié l'appel
        // (caller_type=User). On notifie les DEUX channels (caller + receiver)
        // comme le fait CallOrchestrator::notifyOtherParty() pour les autres
        // types d'appels — sans effet néfaste, celui qui a raccroché lui-même
        // ignore simplement l'event côté client.
        $this->notifyBothParties($call, 'call.ended', ['duration' => $duration]);

        Log::info('📵 Appel terminé par chauffeur', [
            'call_id'  => $call->id,
            'duration' => $duration,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Appel terminé.',
            'duration' => $duration,
        ]);
    }

    /**
     * Marquer l'appel comme manqué (timeout 30s côté Flutter).
     */
    public function missed(Request $request, $callId): JsonResponse
    {
        $call = Call::with('trip')->find($callId);

        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $call->update(['status' => 'missed', 'ended_at' => now()]);

        $this->notifyBothParties($call, 'call.ended', ['missed' => true]);

        return response()->json(['success' => true, 'message' => 'Appel marqué manqué.']);
    }

    /**
     * GET /driver/calls/{callId}/status — polling de secours : si l'event
     * Pusher "call.ended" échoue silencieusement (identifiants Pusher
     * potentiellement invalides), l'app appelante interroge ce endpoint
     * pendant un appel actif pour rattraper la fin d'appel côté serveur.
     * Même pattern que AdminCallController::status()/CompanyCallController::status().
     */
    public function status(Request $request, $callId): JsonResponse
    {
        $call = Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true, 'status' => $call->status]);
    }

    /**
     * Arrête l'enregistrement Cloud Recording (s'il a été démarré à la
     * réponse) et enregistre le fichier produit dans `call_recordings`
     * (source='cloud', disque `agora_recordings`) — visible dans la liste
     * admin des enregistrements au même titre que les appels support.
     * Sans effet si Cloud Recording n'était pas configuré/démarré pour cet
     * appel (les champs recording_* sont alors null).
     */
    private function stopCloudRecording(Call $call): void
    {
        if (!$call->recording_resource_id || !$call->recording_sid) {
            return;
        }

        // Best-effort total : ne doit JAMAIS empêcher le raccroché de se
        // terminer normalement, quelle que soit l'erreur (réseau Agora,
        // contrainte DB, etc.).
        try {
            $channel = AgoraTokenService::channelForCall($call->id);
            $files = AgoraCloudRecordingService::stop(
                $call->id, $channel, $call->recording_resource_id, $call->recording_sid, (int) $call->recording_uid
            );

            foreach ($files as $filePath) {
                CallRecording::create([
                    'call_id'          => $call->id,
                    'source'           => 'cloud',
                    // Valeurs neutres non-null : un enregistrement Cloud
                    // Recording n'est "enregistré par" aucune personne
                    // précise (contrairement aux enregistrements navigateur
                    // admin/société) — recorded_by_type/id restent
                    // fonctionnellement vides même si la colonne DB n'a pas
                    // pu être rendue nullable (doctrine/dbal absent).
                    'recorded_by_type' => 'system',
                    'recorded_by_id'   => 0,
                    'path'             => $filePath,
                    'storage_disk'     => 'agora_recordings',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('stopCloudRecording error: ' . $e->getMessage(), ['call_id' => $call->id]);
        }
    }

    /**
     * Notifie les DEUX channels (caller + receiver) qu'un appel vient de se
     * terminer — on ne sait pas ici qui a raccroché en premier, donc les deux
     * sont notifiés ; celui qui a lui-même déclenché l'action ignore
     * simplement l'event côté client. Évite le bug où seul le receiver
     * "supposé" (toujours le client) était notifié, cassant le raccroché
     * automatique quand c'est le client qui avait initié l'appel.
     */
    private function notifyBothParties(Call $call, string $event, array $extra = []): void
    {
        $payload = array_merge([
            'call_id' => $call->id,
            'trip_id' => $call->trip_id,
        ], $extra);

        $channels = [
            $call->caller_type === \App\Models\Driver\Driver::class
                ? "driver.{$call->caller_id}" : "user.{$call->caller_id}",
            $call->receiver_type === \App\Models\Driver\Driver::class
                ? "driver.{$call->receiver_id}" : "user.{$call->receiver_id}",
        ];

        foreach (array_unique($channels) as $channel) {
            try {
                $this->pusher()->trigger($channel, $event, $payload);
            } catch (\Exception $e) {
                Log::warning('Pusher ' . $event . ' error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Historique des appels d'un trajet.
     */
    public function history(Request $request, $tripId): JsonResponse
    {
        $driver = $request->user();

        // Vérifier que le trajet appartient à ce chauffeur
        $trip = Trip::where('id', $tripId)
            ->where('driver_id', $driver->id)
            ->first();

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trajet introuvable.'], 404);
        }

        $calls = Call::forTrip($tripId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => [
                'id'                 => $c->id,
                'type'               => $c->type,
                'status'             => $c->status,
                'duration_seconds'   => $c->duration_seconds,
                'duration_formatted' => $c->duration_formatted,
                'started_at'         => $c->started_at?->toIso8601String(),
                'ended_at'           => $c->ended_at?->toIso8601String(),
                'created_at'         => $c->created_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'calls' => $calls]);
    }
}
