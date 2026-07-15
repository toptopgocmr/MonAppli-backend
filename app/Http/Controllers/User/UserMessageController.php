<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Trip;
use App\Models\Booking;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * UserMessageController — Messagerie client ↔ chauffeur
 * 🔄 MODIFIÉ : modération complète + corrections de structure
 *
 * Corrections apportées vs votre version originale :
 *   - index()  : requête via bookings (pas user_id sur Message)
 *   - show()   : vérification via booking (pas user_id sur Message)
 *   - store()  : sender_id/receiver_id corrects + modération complète
 *                (votre original ne loggait pas et manquait insultes/romantique)
 */
class UserMessageController extends Controller
{
    // ── Modération complète (miroir Flutter ModerationService) ─────────────
    private const PHONE_REGEX    = '/(\+?\d[\d\s\-\.\/\(\)]{6,}\d)/';
    private const EMAIL_REGEX    = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';
    private const URL_REGEX      = '/(https?:\/\/|www\.|bit\.ly|t\.me|wa\.me|\.(com|fr|net|org|io|co|me))/i';

    private const THREATS = [
        'je vais te tuer', 'je te tue', 'mort à', 'je vais te retrouver',
        'tu vas mourir', 'je te retrouve', 'je te fracasse', 'on se retrouve',
        'gare à toi', 'tu vas regretter', 'je vais te buter', 'crève',
        'je te massacre', 'prépare-toi',
    ];

    private const INSULTS = [
        'fils de pute', 'fdp', 'connard', 'connasse', 'salope', 'pute',
        'enculé', 'batard', 'bâtard', 'nique ta mère', 'ntm',
        'va te faire foutre', 'va te faire enculer',
    ];

    // ✅ AJOUTÉ — contenu sensuel/romantique/drague (fusionné avec l'ancienne
    // liste ROMANTIC, qui ne couvrait que "je t'aime" et le partage de numéro).
    private const ROMANTIC = [
        'je t\'aime', 'je taime', 'je vous aime', 'donne-moi ton numéro',
        'donne moi ton numero', 'ton whatsapp', 'viens chez moi',
        'tu es belle', 'tu es beau', 'tu es sexy', 'tu me plais',
        'tu me plait', 'on peut se voir', 'on se voit ce soir',
        'rendez-vous amoureux', 'tu veux sortir avec moi', 'es-tu libre ce soir',
        'je te trouve attirante', 'je te trouve attirant', 'mon coeur',
        'ma chérie', 'mon chéri', 'bisous', 'je craque pour toi',
        'tu me manques', 'petit ami', 'petite amie', 'sortir ensemble',
        'câlin', 'calin',
    ];

    // ✅ AJOUTÉ — propos haineux/discriminatoires (racisme, xénophobie...).
    private const HATEFUL = [
        'sale race', 'sale étranger', 'sale etranger', 'rentre chez toi',
        'rentre dans ton pays', 'retourne dans ton pays', 'sous-race',
        'sous race', 'racaille', 'espèce de sous-race',
        'sale noir', 'sale arabe', 'sale blanc', 'sale juif',
        'nique ta race', 'nique ta religion',
    ];

    // ✅ AJOUTÉ — manque de respect / propos dégradants (distinct des
    // insultes vulgaires déjà couvertes par INSULTS).
    private const DISRESPECT = [
        'ferme ta gueule', 'ta gueule', 'tais-toi', 'obéis', 'tu es nul',
        'tu es nulle', 'tu ne sers à rien', 'tu ne sers a rien',
        'moins que rien', 'bon à rien', 'bon a rien', 'incapable',
        'tu es stupide', 'tu es débile', 'tu es debile', 'dégage',
        'degage', 'sous-merde', 'sous merde', 'tu me fais perdre mon temps',
    ];

    private const OFF_PLATFORM = [
        'payer en cash', 'payer en liquide', 'paiement direct',
        'mobile money direct', 'orange money direct', 'sans l\'appli',
    ];

    private function moderate(string $text): ?string
    {
        $t        = mb_strtolower($text);
        $collapsed = preg_replace('/[\s\-\.\(\)]/', '', $text);

        if (preg_match(self::PHONE_REGEX, $collapsed) && preg_match('/\d{7,}/', $collapsed)) {
            return 'numéro de téléphone';
        }
        if (preg_match(self::EMAIL_REGEX, $text))  return 'adresse e-mail';
        if (preg_match(self::URL_REGEX, $t))        return 'lien externe';

        foreach (self::THREATS      as $w) { if (str_contains($t, $w)) return 'menace'; }
        foreach (self::INSULTS      as $w) { if (str_contains($t, $w)) return 'insulte'; }
        foreach (self::HATEFUL      as $w) { if (str_contains($t, $w)) return 'propos haineux'; }
        foreach (self::DISRESPECT   as $w) { if (str_contains($t, $w)) return 'manque de respect'; }
        foreach (self::ROMANTIC     as $w) { if (str_contains($t, $w)) return 'contenu inapproprié'; }
        foreach (self::OFF_PLATFORM as $w) { if (str_contains($t, $w)) return 'paiement hors plateforme'; }

        return null;
    }

    /**
     * GET /api/user/messages
     * Liste des conversations du client (une par trajet)
     * FIX : Message n'a pas de user_id — on passe par bookings
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $bookings = Booking::where('user_id', $user->id)
            ->whereHas('trip.messages')
            ->with(['trip' => fn($q) => $q->with([
                'driver',
                'messages' => fn($q2) => $q2
                    ->where('refused', false)
                    ->latest()
                    ->limit(1),
            ])])
            ->get();

        $data = $bookings->map(function ($booking) {
            $trip   = $booking->trip;
            $driver = $trip?->driver;
            $last   = $trip?->messages->first();

            $driverPhoto = null;
            if ($driver?->profile_photo) {
                $driverPhoto = str_starts_with($driver->profile_photo, 'http')
                    ? $driver->profile_photo
                    : asset('storage/' . $driver->profile_photo);
            }

            $unread = Message::where('trip_id', $trip?->id)
                ->where('sender_type', 'like', '%Driver%')
                ->where('is_read', false)
                ->where('refused', false)
                ->count();

            return [
                'trip_id'      => $trip?->id,
                'driver_id'    => $driver?->id,
                'driver_name'  => $driver
                    ? trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? ''))
                    : 'Chauffeur',
                'driver_photo' => $driverPhoto,
                // ⚠️ Numéro personnel du chauffeur volontairement absent : appel in-app uniquement.
                'trip_status'  => $trip?->status ?? 'pending',
                'last_message' => $last?->content ?? '',
                'updated_at'   => $last?->created_at ?? $trip?->updated_at,
                'unread_count' => $unread,
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/user/messages/{tripId}
     * Historique messages d'un trajet
     * FIX : vérification via booking (pas user_id sur Message)
     */
    public function show(Request $request, $tripId)
    {
        $user = Auth::user();

        // Le chat n'est accessible qu'après paiement de la réservation
        // (voir Booking::isPaid()/scopePaid() — source de vérité unique).
        $hasAccess = Booking::where('user_id', $user->id)
            ->where('trip_id', $tripId)
            ->paid()
            ->exists();

        // Permettre aussi si le trip appartient directement au client (cas course directe)
        if (!$hasAccess) {
            $hasAccess = Trip::where('id', $tripId)
                ->where('user_id', $user->id)
                ->exists();
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Le chat est disponible une fois votre réservation payée.'], 403);
        }

        $messages = Message::where('trip_id', $tripId)
            ->oldest()
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'content'     => $m->content,
                'sender'      => str_contains($m->sender_type, 'Driver') ? 'driver' : 'client',
                'sender_type' => $m->sender_type,
                'is_read'     => (bool) $m->is_read,
                'refused'     => (bool) ($m->refused ?? false),
                'created_at'  => $m->created_at?->toIso8601String(),
            ]);

        // Marquer les messages chauffeur comme lus
        Message::where('trip_id', $tripId)
            ->where('sender_type', 'like', '%Driver%')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * POST /api/user/messages/{tripId}
     * Envoi d'un message avec modération serveur
     * FIX : sender_id/receiver_id corrects, namespaces User\User
     */
    public function store(Request $request, $tripId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user    = Auth::user();
        $content = trim($request->content);

        // Vérifier l'accès
        $trip = Trip::find($tripId);
        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trajet introuvable.'], 404);
        }

        $hasAccess = Booking::where('user_id', $user->id)
            ->where('trip_id', $tripId)
            ->paid()
            ->exists() || $trip->user_id == $user->id;

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Le chat est disponible une fois votre réservation payée.'], 403);
        }

        // ── Modération côté serveur ──────────────────────────────────────
        $reason = $this->moderate($content);
        if ($reason) {
            Log::warning('🚫 Message client bloqué', [
                'user_id' => $user->id,
                'trip_id' => $tripId,
                'reason'  => $reason,
                'content' => substr($content, 0, 80),
            ]);

            // Enregistrer pour traçabilité admin
            if ($trip->driver_id) {
                Message::create([
                    'trip_id'        => $tripId,
                    'sender_type'    => get_class($user),  // App\Models\User\User
                    'sender_id'      => $user->id,
                    'receiver_type'  => \App\Models\Driver\Driver::class,
                    'receiver_id'    => $trip->driver_id,
                    'content'        => $content,
                    'refused'        => true,
                    'refused_reason' => $reason,
                ]);
            }

            return response()->json([
                'success' => false,
                'blocked' => true,
                'reason'  => $reason,
                'message' => 'Message refusé par la modération.',
            ], 422);
        }

        // ── Sauvegarde ───────────────────────────────────────────────────
        $message = Message::create([
            'trip_id'       => $tripId,
            'sender_type'   => get_class($user),  // App\Models\User\User
            'sender_id'     => $user->id,
            'receiver_type' => \App\Models\Driver\Driver::class,
            'receiver_id'   => $trip->driver_id,
            'content'       => $content,
            'refused'       => false,
        ]);

        // ── Diffusion Pusher ──────────────────────────────────────────────
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Pusher broadcast error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
     