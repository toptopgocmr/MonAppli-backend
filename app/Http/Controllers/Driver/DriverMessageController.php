<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\User\User;
use App\Http\Resources\MessageResource;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverMessageController extends Controller
{
    // ── Modération ────────────────────────────────────────────────────────
    private const PHONE_REGEX    = '/(\+?\d[\d\s\-\.\/\(\)]{6,}\d)/';
    private const EMAIL_REGEX    = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';
    private const URL_REGEX      = '/(https?:\/\/|www\.|bit\.ly|t\.me|wa\.me|\.(com|fr|net|org|io|co|me))/i';

    private const THREATS      = ['je vais te tuer','je te tue','mort à','tu vas mourir','je te retrouve','je te fracasse','gare à toi','tu vas regretter','je vais te buter','crève','je te massacre','prépare-toi'];
    private const INSULTS      = ['fils de pute','fdp','connard','connasse','salope','pute','enculé','batard','bâtard','nique ta mère','ntm','va te faire foutre','va te faire enculer'];
    private const ROMANTIC     = ['je t\'aime','je taime','je vous aime','je veux te faire','donne-moi ton numéro','donne moi ton numero','ton whatsapp','viens chez moi'];
    private const OFF_PLATFORM = ['payer en cash','payer en liquide','paiement direct','mobile money direct','orange money direct','sans l\'appli'];

    private function moderate(string $text): ?string
    {
        $t        = mb_strtolower($text);
        $collapsed = preg_replace('/[\s\-\.\(\)]/', '', $text);

        if (preg_match(self::PHONE_REGEX, $collapsed) && preg_match('/\d{7,}/', $collapsed)) return 'numéro de téléphone';
        if (preg_match(self::EMAIL_REGEX, $text))  return 'adresse e-mail';
        if (preg_match(self::URL_REGEX, $t))        return 'lien externe';

        foreach (self::THREATS      as $w) { if (str_contains($t, $w)) return 'menace'; }
        foreach (self::INSULTS      as $w) { if (str_contains($t, $w)) return 'insulte'; }
        foreach (self::ROMANTIC     as $w) { if (str_contains($t, $w)) return 'contenu inapproprié'; }
        foreach (self::OFF_PLATFORM as $w) { if (str_contains($t, $w)) return 'paiement hors plateforme'; }

        return null;
    }

    /**
     * ✅ FIX index() :
     * Avant : $trip->user via user_id (champ absent sur trips)
     * Après : on passe par les bookings pour trouver les clients
     */
    public function index(Request $request)
    {
        $driver = $request->user();

        // ✅ Récupérer les trajets du chauffeur qui ont des réservations
        $trips = Trip::where('driver_id', $driver->id)
            ->whereHas('bookings')
            ->with([
                'bookings.user',
                'messages' => function ($q) {
                    $q->where('refused', false)->latest()->limit(1);
                },
            ])
            ->latest()
            ->get();

        $data = [];

        foreach ($trips as $trip) {
            // Un trip peut avoir plusieurs clients — on crée une entrée par client
            foreach ($trip->bookings as $booking) {
                $user = $booking->user;
                if (!$user) continue;

                $lastMessage = $trip->messages->first();

                $clientPhoto = null;
                if ($user->profile_photo) {
                    $clientPhoto = str_starts_with($user->profile_photo, 'http')
                        ? $user->profile_photo
                        : asset('storage/' . $user->profile_photo);
                }

                $unread = Message::where('trip_id', $trip->id)
                    ->where('sender_type', User::class)
                    ->where('is_read', false)
                    ->where('refused', false)
                    ->count();

                $data[] = [
                    'trip_id'        => $trip->id,
                    'client_id'      => $user->id,
                    'client_name'    => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'client_photo'   => $clientPhoto,
                    'client_phone'   => $user->phone ?? '',
                    'booking_status' => $booking->status ?? 'pending',
                    'trip_status'    => $trip->status ?? 'pending',
                    'last_message'   => $lastMessage?->content ?? '',
                    'updated_at'     => $lastMessage?->created_at ?? $trip->updated_at,
                    'unread_count'   => $unread,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── show() — inchangé ─────────────────────────────────────────────────
    public function show(Request $request, $tripId)
    {
        $driver = $request->user();

        $trip = Trip::where('id', $tripId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        $messages = Message::where('trip_id', $tripId)
            ->where('refused', false)
            ->oldest()
            ->get();

        // Marquer comme lus
        Message::where('trip_id', $tripId)
            ->where('receiver_id', $driver->id)
            ->where('receiver_type', get_class($driver))
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success'  => true,
            'messages' => MessageResource::collection($messages),
        ]);
    }

    // ── store() — avec modération ─────────────────────────────────────────
    public function store(Request $request, $tripId)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $driver  = $request->user();
        $content = trim($request->content);

        $trip = Trip::where('id', $tripId)
            ->where('driver_id', $driver->id)
            ->firstOrFail();

        // ✅ Vérifier qu'il y a bien un client qui a réservé ce trajet
        $booking = Booking::where('trip_id', $tripId)
            ->whereIn('status', ['pending', 'confirmed', 'paid', 'completed'])
            ->first();

        $receiverId   = $booking?->user_id ?? $trip->user_id;
        $receiverType = User::class;

        // Modération
        $reason = $this->moderate($content);
        if ($reason) {
            Log::warning('🚫 Message chauffeur bloqué', [
                'driver_id' => $driver->id, 'trip_id' => $tripId,
                'reason' => $reason, 'content' => substr($content, 0, 80),
            ]);
            Message::create([
                'trip_id'        => $tripId,
                'sender_type'    => get_class($driver),
                'sender_id'      => $driver->id,
                'receiver_type'  => $receiverType,
                'receiver_id'    => $receiverId,
                'content'        => $content,
                'refused'        => true,
                'refused_reason' => $reason,
            ]);
            return response()->json([
                'success' => false, 'blocked' => true,
                'reason'  => $reason, 'message' => 'Message refusé par la modération.',
            ], 422);
        }

        $message = Message::create([
            'trip_id'       => $tripId,
            'sender_type'   => get_class($driver),
            'sender_id'     => $driver->id,
            'receiver_type' => $receiverType,
            'receiver_id'   => $receiverId,
            'content'       => $content,
        ]);

        try { MessageSent::dispatch($message); }
        catch (\Exception $e) { Log::warning('Pusher: ' . $e->getMessage()); }

        return new MessageResource($message);
    }
}
