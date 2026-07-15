<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\Call;
use App\Models\CallRecording;
use App\Models\User\User;
use App\Services\Agora\AgoraCloudRecordingService;
use App\Services\Agora\AgoraTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class UserCallController extends Controller
{
    private function pusher(): Pusher
    {
        return new Pusher(
            env('PUSHER_APP_KEY',    'b3f32e17cb1b81e87214'),
            env('PUSHER_APP_SECRET', ''),
            env('PUSHER_APP_ID',     ''),
            ['cluster' => env('PUSHER_APP_CLUSTER', 'eu'), 'useTLS' => true]
        );
    }

    /**
     * POST /user/calls/{tripId}/initiate
     * ✅ Le client initie un appel → on broadcaste sur le channel PERSONNEL
     * du chauffeur (driver.{driver_id}), avec l'event 'call.incoming'.
     *
     * IMPORTANT : c'est exactement ce que PusherService._subscribeDriver()
     * écoute côté app Chauffeur (voir Mobile-Chauffeur-main/lib/core/services/
     * pusher_service.dart). La version précédente broadcastait sur
     * "trip.{tripId}" avec l'event "call.initiated" — un channel/event que
     * l'app Chauffeur n'écoute jamais (elle n'écoute que son propre channel
     * driver.{id} avec l'event "call.incoming"), donc le téléphone du
     * chauffeur ne sonnait jamais.
     */
    public function initiate(Request $request, $tripId)
    {
        $user = $request->user();

        // ✅ L'appel n'est autorisé qu'une fois la réservation payée — même
        // règle que pour le chat (voir Booking::isPaid()/scopePaid()).
        // Avant : whereIn('status', ['confirmed','paid','pending']) laissait
        // appeler le chauffeur dès une réservation 'pending', avant paiement.
        $booking = Booking::where('trip_id', $tripId)
            ->where('user_id', $user->id)
            ->paid()
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'L\'appel est disponible une fois votre réservation payée.',
            ], 404);
        }

        $trip = Trip::find($tripId);
        if (!$trip || !$trip->driver_id) {
            return response()->json([
                'success' => false,
                'message' => 'Chauffeur introuvable pour ce trajet.',
            ], 404);
        }

        // Un seul appel actif à la fois sur ce trajet — sauf s'il est
        // "abandonné" (personne n'a décroché, app fermée sans raccrocher),
        // auquel cas on le referme automatiquement pour ne pas bloquer.
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
            'caller_type'   => User::class,
            'caller_id'     => $user->id,
            'receiver_type' => \App\Models\Driver\Driver::class,
            'receiver_id'   => $trip->driver_id,
            'type'          => $request->input('type', 'audio'),
            'status'        => 'initiated',
            'started_at'    => now(),
        ]);

        $callerName  = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $callerPhoto = '';
        if ($user->profile_photo) {
            $callerPhoto = str_starts_with($user->profile_photo, 'http')
                ? $user->profile_photo
                : asset('storage/' . $user->profile_photo);
        }

        // ✅ Broadcaster l'événement call.incoming sur le channel du chauffeur
        try {
            $this->pusher()->trigger("driver.{$trip->driver_id}", 'call.incoming', [
                'trip_id'      => (int) $tripId,
                'call_id'      => $call->id,
                'caller_id'    => $user->id,
                'caller_name'  => $callerName ?: 'Client',
                'caller_photo' => $callerPhoto,
                'initiated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Pusher call broadcast error: ' . $e->getMessage());
        }

        Log::info('📞 Appel initié', [
            'call_id'   => $call->id,
            'user_id'   => $user->id,
            'trip_id'   => $tripId,
            'driver_id' => $trip->driver_id,
        ]);

        // ✅ Audio réel (Agora) : le client qui initie l'appel rejoint tout de
        // suite le canal dédié à cet appel. Le chauffeur récupère le sien via
        // GET /driver/calls/{callId}/token une fois la notification reçue.
        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForUser($user->id));

        return response()->json([
            'success' => true,
            'message' => 'Appel initié.',
            'call'    => [
                'id'      => $call->id,
                'trip_id' => (int) $tripId,
            ],
            'agora' => $agora,
        ]);
    }

    /**
     * GET /user/calls/{callId}/token
     * Récupère (ou régénère) le token Agora du client pour un appel en cours,
     * qu'il en soit l'appelant ou le destinataire (appel initié par le chauffeur).
     */
    public function token(Request $request, $callId)
    {
        $user = $request->user();
        $call = Call::find($callId);

        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $isParticipant =
            ($call->caller_type === User::class && (int) $call->caller_id === (int) $user->id) ||
            ($call->receiver_type === User::class && (int) $call->receiver_id === (int) $user->id);

        if (!$isParticipant) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForUser($user->id));

        if (!$agora) {
            return response()->json([
                'success' => false,
                'message' => "Le service d'appel audio n'est pas configuré.",
            ], 503);
        }

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * POST /user/calls/{callId}/end
     */
    public function end(Request $request, $callId)
    {
        $call = Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        // ✅ abs() : selon la version de Carbon, diffInSeconds() n'est plus
        // toujours absolu par défaut — sans ça la durée affichée pouvait
        // être négative.
        $duration = $call->started_at ? (int) abs(now()->diffInSeconds($call->started_at)) : 0;

        $call->update([
            'status'           => 'ended',
            'duration_seconds' => $duration,
            'ended_at'         => now(),
        ]);

        $this->stopCloudRecording($call);

        // ✅ FIX : "driver.{$call->receiver_id}" supposait à tort que le
        // chauffeur est toujours receiver — faux si c'est LUI qui avait
        // initié l'appel (caller_type=Driver). On notifie les DEUX channels.
        $this->notifyBothParties($call, 'call.ended', ['duration' => $duration]);

        return response()->json(['success' => true, 'message' => 'Appel terminé.', 'duration' => $duration]);
    }

    /**
     * POST /user/calls/{callId}/answer
     */
    public function answer(Request $request, $callId)
    {
        $call = Call::find($callId);
        if (!$call || $call->status !== 'initiated') {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $call->update(['status' => 'answered', 'started_at' => now()]);

        // ✅ Le client (destinataire d'un appel initié par le chauffeur) reçoit
        // ici son token Agora pour rejoindre le même canal que l'appelant.
        $channel = AgoraTokenService::channelForCall($call->id);
        $agora   = AgoraTokenService::generate($channel, AgoraTokenService::uidForUser($request->user()->id));

        // 🎙️ Démarrage Cloud Recording (voir DriverCallController::answer()
        // pour le détail — les deux contrôleurs partagent le même appel Call,
        // celui-ci ne démarre l'enregistrement que si l'AUTRE contrôleur ne
        // l'a pas déjà fait — vérifié via recording_resource_id).
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
     * POST /user/calls/{callId}/missed
     */
    public function missed(Request $request, $callId)
    {
        $call = Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $call->update(['status' => 'missed', 'ended_at' => now()]);

        $this->notifyBothParties($call, 'call.ended', ['missed' => true]);

        return response()->json(['success' => true, 'message' => 'Appel marqué manqué.']);
    }

    /**
     * GET /user/calls/{callId}/status — polling de secours, même pattern que
     * DriverCallController::status()/AdminCallController::status().
     */
    public function status(Request $request, $callId)
    {
        $call = Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true, 'status' => $call->status]);
    }

    /**
     * Arrête l'enregistrement Cloud Recording (s'il a été démarré à la
     * réponse) et enregistre le fichier produit dans `call_recordings` —
     * voir DriverCallController::stopCloudRecording() pour le détail.
     */
    private function stopCloudRecording(Call $call): void
    {
        if (!$call->recording_resource_id || !$call->recording_sid) {
            return;
        }

        // Best-effort total : voir DriverCallController::stopCloudRecording()
        // pour le détail (ne doit jamais empêcher le raccroché de finir).
        try {
            $channel = AgoraTokenService::channelForCall($call->id);
            $files = AgoraCloudRecordingService::stop(
                $call->id, $channel, $call->recording_resource_id, $call->recording_sid, (int) $call->recording_uid
            );

            foreach ($files as $filePath) {
                CallRecording::create([
                    'call_id'          => $call->id,
                    'source'           => 'cloud',
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
     * Notifie les DEUX channels (caller + receiver) — voir
     * DriverCallController::notifyBothParties() pour le détail du bug évité.
     */
    private function notifyBothParties(Call $call, string $event, array $extra = []): void
    {
        $payload = array_merge([
            'call_id' => $call->id,
            'trip_id' => $call->trip_id,
        ], $extra);

        $channels = [
            $call->caller_type === User::class
                ? "user.{$call->caller_id}" : "driver.{$call->caller_id}",
            $call->receiver_type === User::class
                ? "user.{$call->receiver_id}" : "driver.{$call->receiver_id}",
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
     * GET /user/calls/{tripId} — historique appels
     */
    public function history(Request $request, $tripId)
    {
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

        return response()->json(['success' => true, 'data' => $calls]);
    }
}
