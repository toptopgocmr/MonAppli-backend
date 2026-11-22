<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Events\PaymentValidated;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PeexService;
use App\Services\Payment\NotchPayService;
use App\Services\Payment\WacePayService;
use App\Services\Payment\GatewayRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPaymentController extends Controller
{
    public function __construct(
        protected FlutterwaveService $flutterwave,
        protected PeexService        $peex,
        protected NotchPayService    $notchpay,
        protected WacePayService     $wacepay,
        protected GatewayRouter      $gateways,
    ) {}

    // ── Paiement Mobile Money (Peex si le pays est couvert, sinon Flutterwave) ──
    public function mobileMoney(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'phone'      => 'required|string',
        ]);

        // L'app mobile envoie soit 'network' + 'country' (écran multi-pays),
        // soit l'ancien schéma 'provider' (mtn|airtel) + 'country_code'.
        // On accepte les deux pour ne rien casser.
        $network = $request->input('network') ?? $request->input('provider');
        $countryCode = strtoupper($request->input('country') ?? $request->input('country_code', config('flutterwave.country_code', 'CM')));

        if (!$network) {
            return response()->json([
                'success' => false,
                'message' => 'Moyen de paiement (network/provider) manquant.',
            ], 422);
        }

        [$method, $operator] = $this->mapNetwork($network);

        $booking = Booking::with('trip.driver')
            ->where('user_id', Auth::id())
            ->findOrFail($request->booking_id);

        // ✅ Garde-fou : une réservation orpheline (trip supprimé/introuvable)
        // ne doit jamais faire planter le paiement avec une erreur 500 opaque.
        if (!$booking->trip) {
            Log::error('UserPaymentController::mobileMoney: booking sans trip associé', [
                'booking_id' => $booking->id,
                'trip_id'    => $booking->trip_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Le trajet associé à cette réservation est introuvable. Contactez le support.',
            ], 422);
        }

        // ✅ Le client peut payer dès que la réservation est en pending ou confirmed
        if (!in_array($booking->status, ['pending', 'confirmed', 'accepted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être payée (statut: ' . $booking->status . ').',
            ], 422);
        }

        $alreadyPaid = Payment::where('booking_id', $booking->id)
            ->where('status', 'success')
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation a déjà été payée.',
            ], 422);
        }

        $transactionRef = 'TXN-' . strtoupper(Str::random(10));
        $user = Auth::user();

        // ── Choix de la passerelle ────────────────────────────────────
        // Passerelle par défaut puis passerelle de secours (bascule auto),
        // réglables dans l'admin : Finances › Passerelles de paiement.
        // Voir App\Services\Payment\GatewayRouter.
        $chain   = $this->gateways->collectChain($countryCode) ?: ['flutterwave'];
        $gateway = $chain[0];

        // ✅ Taux de commission réellement applicable à CE chauffeur/société,
        // au lieu d'un 10% fixe qui ignorait complètement les taux configurés
        // par l'admin (société : Company::commission_rate, chauffeur
        // indépendant : App\Models\CommissionRate — priorité chauffeur >
        // type de véhicule > pays > taux global).
        $driver = $booking->trip->driver;
        $commissionRatePercent = null;

        if ($driver && $driver->company_id) {
            $company = \App\Models\Company::find($driver->company_id);
            if ($company && $company->commission_rate !== null) {
                $commissionRatePercent = (float) $company->commission_rate;
            }
        }

        if ($commissionRatePercent === null) {
            $commissionRatePercent = \App\Models\CommissionRate::resolveRate(
                (int) ($driver->id ?? 0),
                $driver->vehicle_type ?? '',
                $countryCode
            );
        }

        // Commission chauffeur : sur le prix de la COURSE uniquement.
        $commissionAmount = round($booking->amount * ($commissionRatePercent / 100), 2);
        $driverNetAmount   = round($booking->amount - $commissionAmount, 2);

        // Frais de service client (figés à la réservation ; calculés et
        // enregistrés maintenant pour les réservations plus anciennes).
        $serviceFee = $booking->serviceFeeAmount();
        if ($booking->service_fee === null) {
            $booking->update(['service_fee' => $serviceFee]);
        }
        $totalToPay = round((float) $booking->amount + $serviceFee, 2);

        // ✅ Garde-fou global : toute exception inattendue ici (contrainte
        // DB, colonne enum non élargie, etc.) doit remonter un message JSON
        // exploitable plutôt que le "Server Error" générique de Laravel.
        try {
            // ── Créer le paiement en base (statut pending) ───────────────
            $payment = Payment::create([
                'user_id'         => $user->id,
                'trip_id'         => $booking->trip_id,
                'driver_id'       => $booking->trip->driver_id,
                'booking_id'      => $booking->id,
                'amount'          => $totalToPay,
                'service_fee'     => $serviceFee,
                'commission'      => $commissionAmount,
                'driver_net'      => $driverNetAmount,
                'method'          => $method,
                'provider'        => $gateway,
                'status'          => 'pending',
                'transaction_ref' => $transactionRef,
                'country'         => $countryCode,
                'city'            => $booking->trip->departure_city ?? 'N/A',
                'paid_at'         => null,
            ]);

            $ctx = [
                'reference'     => $transactionRef,
                'phone'         => $request->phone,
                'amount'        => (int) ceil($totalToPay),
                'country'       => $countryCode,
                'operator'      => $operator,
                'customer_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Client TopTopGo',
                'email'         => $user->email ?? "user_{$user->id}@toptopgo.app",
                'first_name'    => $user->first_name ?? null,
                'last_name'     => $user->last_name ?? null,
                'user_id'       => $user->id,
                'booking_id'    => $booking->id,
                'client_ip'     => $request->ip(),
            ];

            // ── Tentatives : défaut, puis secours si la 1re refuse ───────
            $attempts  = [];
            $result    = ['success' => false, 'error' => 'Aucune passerelle disponible'];
            $uncertain = false;

            foreach ($chain as $i => $gw) {
                $gateway = $gw;
                $payment->update(['provider' => $gw]);

                $result = $this->collectWith($gw, $ctx);
                $ok     = (bool) ($result['success'] ?? false);

                $attempts[] = [
                    'gateway' => $gw,
                    'role'    => $i === 0 ? 'défaut' : 'secours',
                    'ok'      => $ok,
                    'error'   => $ok ? null : mb_substr((string) ($result['error'] ?? 'erreur inconnue'), 0, 300),
                    'at'      => now()->toIso8601String(),
                ];

                if ($ok) {
                    if ($i > 0) {
                        Log::warning('Collecte : bascule sur la passerelle de secours', ['booking_id' => $booking->id, 'gateway' => $gw, 'attempts' => $attempts]);
                    }
                    break;
                }

                // ✅ On loggue aussi la réponse BRUTE du fournisseur.
                Log::error('UserPaymentController::mobileMoney error', [
                    'gateway'      => $gw,
                    'booking_id'   => $booking->id,
                    'error'        => $result['error'] ?? 'unknown',
                    'phone_sent'   => $request->phone,
                    'country'      => $countryCode,
                    'amount'       => $totalToPay,
                    'raw_response' => $result['data'] ?? null,
                    'http_status'  => $result['http_status'] ?? null,
                    'raw_body'     => $result['raw_body'] ?? null,
                    'endpoint'     => $result['endpoint'] ?? null,
                ]);

                // Pas de réponse claire (délai dépassé) : la demande a pu
                // partir chez le client → on ne relance PAS ailleurs (sinon
                // double débit possible). Le paiement reste « en attente » et
                // le suivi (polling / webhook) tranchera.
                if ($this->gateways->isUncertainFailure($result)) {
                    $uncertain = true;
                    break;
                }
            }

            $payment->update(['gateway_attempts' => $attempts]);

            if ($uncertain) {
                return response()->json([
                    'success' => true,
                    'message' => 'Demande envoyée. Vérifiez votre téléphone et confirmez si une demande de paiement apparaît.',
                    'data'    => [
                        'transaction_ref' => $transactionRef,
                        'amount'          => $totalToPay,
                        'service_fee'     => $serviceFee,
                        'status'          => 'pending',
                        'instruction'     => 'Vérifiez votre téléphone : confirmez la demande de paiement si elle apparaît. Ne relancez pas un paiement avant la fin de la vérification.',
                        'gateway'         => $gateway,
                        'chat_enabled'    => false,
                        'chat_channel'    => 'chat.trip.' . $booking->trip_id,
                    ],
                ]);
            }

            if (!($result['success'] ?? false)) {
                $rawError = $result['error'] ?? 'Erreur lors de l\'initiation du paiement Mobile Money.';
                $humanized = match ($gateway) {
                    'wacepay'  => $this->wacepay->humanizeFailureReason($rawError) ?? $rawError,
                    'notchpay' => $this->notchpay->humanizeFailureReason($rawError) ?? $rawError,
                    'peex'     => $this->peex->humanizeFailureReason($rawError) ?? $rawError,
                    default    => $rawError,
                };

                // Jamais de message technique brut côté client (ex. Peex :
                // « Endpoint "POST /collection/request_payment" not found. »).
                if (preg_match('/endpoint|not found|curl|http|server error|unauthori[sz]ed|forbidden|token|exception|sqlstate|configur/i', (string) $humanized)) {
                    Log::error('Collecte : erreur technique masquée au client', ['gateway' => $gateway, 'raw' => $humanized, 'attempts' => $attempts]);
                    $humanized = 'Le service de paiement est momentanément indisponible. Réessayez dans quelques minutes.';
                }

                $payment->update(['status' => 'failed', 'failure_reason' => $humanized]);

                return response()->json([
                    'success' => false,
                    'message' => $humanized,
                ], 400);
            }

            // ── Sauvegarder l'identifiant de transaction fournisseur ─────
            if ($gateway === 'flutterwave') {
                $payment->update(['flw_charge_id' => $result['data']['charge_id'] ?? null]);
            } else {
                $payment->update(['provider_transaction_id' => $result['transaction_id'] ?? null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié. Veuillez autoriser la transaction sur votre téléphone.',
                'data'    => [
                    'transaction_ref' => $transactionRef,
                    'amount'          => $totalToPay,
                    'service_fee'     => $serviceFee,
                    'status'          => 'pending',
                    'instruction'     => $result['instruction'] ?? 'Autorisez le paiement sur votre téléphone Mobile Money.',
                    'gateway'         => $gateway,
                    'charge_id'       => $result['data']['charge_id'] ?? null,
                    'chat_enabled'    => false,
                    'chat_channel'    => 'chat.trip.' . $booking->trip_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('UserPaymentController::mobileMoney exception non gérée', [
                'booking_id' => $booking->id,
                'gateway'    => $gateway,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            if (isset($payment)) {
                $payment->update(['status' => 'failed']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur technique est survenue lors de l\'initiation du paiement. Veuillez réessayer.',
            ], 500);
        }
    }

    // ── Statut d'un paiement (polling) ───────────────────────────────
    public function status(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $payment = Payment::where('booking_id', $request->booking_id)
            ->where('user_id', Auth::id())
            ->latest()
            ->firstOrFail();

        // Si toujours en attente, on re-vérifie auprès du bon fournisseur
        if ($payment->status === 'pending') {
            $check = $this->pollProviderStatus($payment);

            if ($check && ($check['success'] ?? false) && isset($check['status'])) {
                $newStatus = $check['status'];

                if (in_array($newStatus, ['success', 'completed'], true)) {
                    $receiptNumber = 'RCP-' . strtoupper(Str::random(8)) . '-' . now()->format('YmdHis');
                    $payment->update([
                        'status'         => 'success',
                        'paid_at'        => now(),
                        'receipt_number' => $receiptNumber,
                    ]);
                    $payment->refresh();

                    $booking = Booking::find($payment->booking_id);
                    if ($booking && $booking->status !== 'paid') {
                        $booking->update(['status' => 'paid']);
                        PaymentValidated::dispatch($booking->load('trip'));
                    }
                } elseif (in_array($newStatus, ['failed', 'cancelled'], true)) {
                    // ✅ FIX : capturer la vraie raison Peex (ex: solde insuffisant)
                    // renvoyée par getTransactionStatus() au lieu du message
                    // générique affiché jusqu'ici côté app client.
                    $payment->update([
                        'status'         => $newStatus,
                        'failure_reason' => $check['reason'] ?? $payment->failure_reason,
                    ]);
                    $payment->refresh();
                }
            }
        }

        $isPaid   = $payment->status === 'success';
        $receipt  = null;

        if ($isPaid) {
            $booking = Booking::with(['trip.driver'])->find($payment->booking_id);
            $receipt = [
                'receipt_number'  => $payment->receipt_number,
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'method'          => $payment->method,
                'paid_at'         => $payment->paid_at,
                'booking_id'      => $payment->booking_id,
                'trip'            => $booking?->trip ? [
                    'departure'      => $booking->trip->departure      ?? $booking->trip->pickup_address,
                    'destination'    => $booking->trip->destination    ?? $booking->trip->dropoff_address,
                    'departure_date' => $booking->trip->departure_date
                        ? \Carbon\Carbon::parse($booking->trip->departure_date)->format('Y-m-d') : null,
                    'departure_time' => $booking->trip->departure_time
                        ? substr($booking->trip->departure_time, 0, 5) : null,
                    'driver_name'    => $booking->trip->driver
                        ? trim(($booking->trip->driver->first_name ?? '') . ' ' . ($booking->trip->driver->last_name ?? ''))
                        : null,
                ] : null,
                'seats'           => $booking?->seats ?? $booking?->passengers ?? 1,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_ref' => $payment->transaction_ref,
                'amount'          => $payment->amount,
                'method'          => $payment->method,
                'status'          => $payment->status,
                'paid_at'         => $payment->paid_at,
                // ✅ FIX : message d'échec précis (ex: "Solde insuffisant...")
                // au lieu du texte générique affiché jusqu'ici par l'app client
                // quel que soit status === 'failed'|'cancelled'.
                'failure_reason'  => $payment->failure_reason,
                'chat_enabled'    => $isPaid,
                'chat_channel'    => 'chat.trip.' . $payment->trip_id,
                'receipt'         => $receipt,
            ],
        ]);
    }

    /**
     * Poll the right provider depending on how the payment was initiated.
     */
    /** Appel de collecte sur UNE passerelle (format commun en entrée). */
    protected function collectWith(string $gateway, array $c): array
    {
        $description = 'TopTopGo - Réservation #' . $c['booking_id'];

        return match ($gateway) {
            'wacepay' => $this->wacepay->collect([
                'reference'     => $c['reference'],
                'phone'         => $c['phone'],
                'amount'        => $c['amount'],
                'currency'      => $this->wacepay->currencyFor($c['country']) ?? 'XAF', // XAF/XOF parité 1:1
                'country'       => $c['country'],
                'operator'      => $c['operator'],
                'customer_name' => $c['customer_name'],
                'email'         => $c['email'],
            ]),
            'notchpay' => $this->notchpay->collect([
                'reference'     => $c['reference'],
                'phone'         => $c['phone'],
                'amount'        => $c['amount'],
                'currency'      => 'XAF',
                'country'       => $c['country'],
                'operator'      => $c['operator'],
                'customer_name' => $c['customer_name'],
                'email'         => $c['email'],
                'description'   => $description,
                'client_ip'     => $c['client_ip'],
            ]),
            'peex' => $this->peex->collect([
                'track_id'      => $c['reference'],
                'phone'         => $c['phone'],
                'amount'        => $c['amount'],
                'currency'      => 'XAF',
                'customer_name' => $c['customer_name'],
                'country'       => $c['country'],
                'description'   => $description,
            ]),
            default => $this->flutterwave->collect([
                'phone'        => $c['phone'],
                'country_code' => $c['country'],
                'amount'       => $c['amount'],
                'operator'     => $c['operator'],
                'reference'    => $c['reference'],
                'description'  => $description,
                'user_id'      => $c['user_id'],
                'email'        => $c['email'],
                'first_name'   => $c['first_name'],
                'last_name'    => $c['last_name'],
            ]),
        };
    }

    protected function pollProviderStatus(Payment $payment): ?array
    {
        if ($payment->provider === 'wacepay') {
            return $this->wacepay->getTransactionStatus($payment->transaction_ref);
        }

        if ($payment->provider === 'notchpay') {
            return $this->notchpay->getTransactionStatus($payment->provider_transaction_id ?: $payment->transaction_ref);
        }

        if ($payment->provider === 'peex') {
            return $this->peex->getTransactionStatus($payment->transaction_ref);
        }

        if (!empty($payment->flw_charge_id)) {
            return $this->flutterwave->getTransactionStatus($payment->flw_charge_id);
        }

        return null;
    }

    /**
     * Map the network string sent by the app (or the legacy 'provider' field)
     * to [enum method value stored in DB, operator code used by gateways].
     *
     * `payments.method` is a DB enum (widened in
     * 2026_07_05_000011_widen_payments_method_enum.php to cover every
     * operator actually offered by the mobile client's payment screen:
     * mtn, orange, airtel, moov, visa, mastercard, wave, free, mobicash,
     * vodafone, mpesa, zamtel, tnm, halopesa, airteltigo, tigo) — an
     * unrecognized value still falls back safely to 'mtn'.
     *
     * The operator code (2nd element) is passed through as-is (uppercased)
     * instead of being collapsed into one of a handful of buckets: Peex and
     * Flutterwave need the exact network the mobile app sent (e.g. MPESA,
     * WAVE, AIRTELTIGO), otherwise the charge is routed to the wrong network.
     */
    protected function mapNetwork(string $network): array
    {
        $normalized = strtoupper(trim($network));

        $method = match (true) {
            str_contains($normalized, 'MTN')        => 'mtn',
            str_contains($normalized, 'ORANGE')     => 'orange',
            str_contains($normalized, 'AIRTELTIGO') => 'airteltigo',
            str_contains($normalized, 'AIRTEL')     => 'airtel',
            str_contains($normalized, 'MOOV') || str_contains($normalized, 'TMONEY') => 'moov',
            str_contains($normalized, 'WAVE')       => 'wave',
            str_contains($normalized, 'FREE')       => 'free',
            str_contains($normalized, 'MOBICASH')   => 'mobicash',
            str_contains($normalized, 'VODAFONE') || str_contains($normalized, 'TELECEL') => 'vodafone',
            str_contains($normalized, 'MPESA')      => 'mpesa',
            str_contains($normalized, 'ZAMTEL')     => 'zamtel',
            str_contains($normalized, 'TNM')        => 'tnm',
            str_contains($normalized, 'HALOPESA')   => 'halopesa',
            str_contains($normalized, 'TIGO')       => 'tigo',
            default => 'mtn',
        };

        $operator = $normalized !== '' ? $normalized : 'MTN';

        return [$method, $operator];
    }
}
