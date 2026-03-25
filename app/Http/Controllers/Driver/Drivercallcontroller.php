<?php

namespace App\Http\Controllers\Driver;

use App\Events\CallInitiated;
use App\Events\CallEnded;
use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DriverCallController — Appels voix in-app côté Chauffeur
 *
 * Routes (dans api.php) :
 *   POST  /api/driver/calls/{trip_id}/initiate  → appeler le client
 *   POST  /api/driver/calls/{call_id}/answer    → décrocher
 *   POST  /api/driver/calls/{call_id}/end       → raccrocher
 *   POST  /api/driver/calls/{call_id}/missed    → marquer manqué
 *   GET   /api/driver/calls/{trip_id}           → historique
 */
class DriverCallController extends Controller
{
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

        // Vérifier qu'il n'y a pas déjà un appel actif sur ce trajet
        $active = Call::forTrip($tripId)->active()->first();
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

        // 📡 Pusher → bannière Flutter sur l'app client
        try {
            broadcast(new CallInitiated($call))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Pusher CallInitiated error: ' . $e->getMessage());
        }

        Log::info('📞 Appel initié par chauffeur', [
            'call_id'   => $call->id,
            'driver_id' => $driver->id,
            'trip_id'   => $tripId,
            'user_id'   => $trip->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appel initié. En attente de réponse du client.',
            'call'    => [
                'id'      => $call->id,
                'trip_id' => $call->trip_id,
                'type'    => $call->type,
                'status'  => $call->status,
            ],
        ]);
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

        return response()->json(['success' => true, 'message' => 'Appel décroché.']);
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

        $duration = $call->started_at
            ? (int) now()->diffInSeconds($call->started_at)
            : 0;

        $call->update([
            'status'           => 'ended',
            'duration_seconds' => $duration,
            'ended_at'         => now(),
        ]);

        try {
            broadcast(new CallEnded($call))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Pusher CallEnded error: ' . $e->getMessage());
        }

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

        try {
            broadcast(new CallEnded($call))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Pusher CallEnded error: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Appel marqué manqué.']);
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