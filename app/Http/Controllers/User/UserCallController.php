<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class UserCallController extends Controller
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
     * POST /user/calls/{tripId}/initiate
     * ✅ Le client initie un appel → on broadcaste sur trip.{tripId}
     */
    public function initiate(Request $request, $tripId)
    {
        $user = $request->user();

        // Vérifier que le client a bien réservé ce trajet
        $booking = Booking::where('trip_id', $tripId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'paid', 'pending'])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation introuvable pour cet appel.',
            ], 404);
        }

        $callerName  = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $callerPhoto = '';
        if ($user->profile_photo) {
            $callerPhoto = str_starts_with($user->profile_photo, 'http')
                ? $user->profile_photo
                : asset('storage/' . $user->profile_photo);
        }

        // ✅ Broadcaster l'événement call.initiated sur le channel du trajet
        try {
            $this->pusher()->trigger("trip.{$tripId}", 'call.initiated', [
                'trip_id'      => (int) $tripId,
                'caller_id'    => $user->id,
                'caller_name'  => $callerName ?: 'Client',
                'caller_photo' => $callerPhoto,
                'initiated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Pusher call broadcast error: ' . $e->getMessage());
        }

        Log::info('📞 Appel initié', [
            'user_id' => $user->id,
            'trip_id' => $tripId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Appel initié.',
            'call'    => [
                'id'      => null, // pas de table calls pour l'instant
                'trip_id' => (int) $tripId,
            ],
        ]);
    }

    /**
     * POST /user/calls/{callId}/end
     */
    public function end(Request $request, $callId)
    {
        return response()->json(['success' => true, 'message' => 'Appel terminé.']);
    }

    /**
     * POST /user/calls/{callId}/answer
     */
    public function answer(Request $request, $callId)
    {
        return response()->json(['success' => true]);
    }

    /**
     * POST /user/calls/{callId}/missed
     */
    public function missed(Request $request, $callId)
    {
        return response()->json(['success' => true]);
    }

    /**
     * GET /user/calls/{tripId} — historique appels
     */
    public function history(Request $request, $tripId)
    {
        return response()->json(['success' => true, 'data' => []]);
    }
}
