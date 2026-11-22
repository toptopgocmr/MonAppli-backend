<?php

namespace App\Services\Payment;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Log;

/**
 * Choix des passerelles de paiement avec bascule automatique.
 *
 *  - Collecte (paiement des réservations) : passerelle par défaut, puis
 *    passerelle de secours si la première refuse ou est en panne.
 *  - Décaissement (retraits chauffeurs / sociétés mobile money) : idem.
 *
 * Réglage dans l'admin (Finances › Passerelles de paiement) ; à défaut,
 * config/payments.php (.env PAYMENT_COLLECT_GATEWAY, PAYMENT_COLLECT_FALLBACK,
 * PAYMENT_PAYOUT_GATEWAY, PAYMENT_PAYOUT_FALLBACK).
 *
 * Sécurité : on ne bascule JAMAIS quand la première passerelle n'a pas
 * répondu à temps (délai dépassé / connexion coupée) — la demande a pu être
 * acceptée de son côté, et une seconde tentative ailleurs risquerait de
 * débiter le client deux fois ou de payer le chauffeur deux fois.
 */
class GatewayRouter
{
    public const COLLECT_GATEWAYS = [
        'wacepay'     => 'Digitwace (WacePay)',
        'peex'        => 'Peex',
        'notchpay'    => 'Notch Pay',
        'flutterwave' => 'Flutterwave',
    ];

    public const PAYOUT_GATEWAYS = [
        'wacepay' => 'Digitwace (WacePay)',
        'peex'    => 'Peex',
    ];

    public function __construct(
        protected WacePayService     $wacepay,
        protected PeexService        $peex,
        protected NotchPayService    $notchpay,
        protected FlutterwaveService $flutterwave,
    ) {
    }

    // ── Réglages ─────────────────────────────────────────────────────

    public function settings(): array
    {
        $g = fn (string $key, string $cfg, $def, array $allowed) => $this->valid(
            PaymentGatewaySetting::getValue($key, config($cfg, $def)), $allowed
        );

        return [
            'collect_primary'  => $g('collect_primary', 'payments.collect_gateway', 'wacepay', self::COLLECT_GATEWAYS) ?? 'wacepay',
            'collect_fallback' => $g('collect_fallback', 'payments.collect_fallback', 'peex', self::COLLECT_GATEWAYS),
            'collect_auto'     => $this->bool(PaymentGatewaySetting::getValue('collect_auto', config('payments.collect_auto_fallback', true))),
            'payout_primary'   => $g('payout_primary', 'payments.payout_gateway', 'wacepay', self::PAYOUT_GATEWAYS) ?? 'wacepay',
            'payout_fallback'  => $g('payout_fallback', 'payments.payout_fallback', 'peex', self::PAYOUT_GATEWAYS),
            'payout_auto'      => $this->bool(PaymentGatewaySetting::getValue('payout_auto', config('payments.payout_auto_fallback', true))),
        ];
    }

    protected function valid($value, array $allowed): ?string
    {
        $v = strtolower(trim((string) $value));
        return array_key_exists($v, $allowed) ? $v : null;
    }

    protected function bool($v): bool
    {
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    // ── Couverture ───────────────────────────────────────────────────

    public function supportsCollect(string $gateway, ?string $country): bool
    {
        $country = strtoupper((string) $country);
        return match ($gateway) {
            'wacepay'     => $this->wacepay->supportsCollectFor($country),
            'peex'        => $country !== '' && $this->peex->supportsCollectFor($country),
            'notchpay'    => $this->notchpay->supportsCollectFor($country),
            'flutterwave' => $this->flutterwave->isAvailable(),
            default       => false,
        };
    }

    public function supportsPayout(string $gateway, ?string $country): bool
    {
        $country = strtoupper((string) $country);
        return match ($gateway) {
            'wacepay' => $this->wacepay->supportsPayoutFor($country),
            'peex'    => $country !== '' && $this->peex->supportsDisbursementFor($country),
            default   => false,
        };
    }

    /**
     * Passerelles de collecte à essayer, dans l'ordre, pour ce pays.
     * Si ni la principale ni la secours ne couvrent le pays, on prend la
     * première passerelle qui le couvre (ex. Flutterwave pour le Ghana).
     */
    public function collectChain(?string $country): array
    {
        $s = $this->settings();
        $chain = [$s['collect_primary']];
        if ($s['collect_auto'] && $s['collect_fallback']) {
            $chain[] = $s['collect_fallback'];
        }
        $chain = array_values(array_filter(array_unique($chain), fn ($g) => $this->supportsCollect($g, $country)));

        if (empty($chain)) {
            foreach (array_keys(self::COLLECT_GATEWAYS) as $g) {
                if ($this->supportsCollect($g, $country)) {
                    return [$g];
                }
            }
        }
        return $chain;
    }

    /** Passerelles de décaissement à essayer, dans l'ordre (vide = paiement manuel). */
    public function payoutChain(?string $country): array
    {
        $s = $this->settings();
        $chain = [$s['payout_primary']];
        if ($s['payout_auto'] && $s['payout_fallback']) {
            $chain[] = $s['payout_fallback'];
        }
        return array_values(array_filter(array_unique($chain), fn ($g) => $this->supportsPayout($g, $country)));
    }

    /**
     * Échec « incertain » : pas de réponse claire de la passerelle (délai
     * dépassé, connexion coupée). La transaction a pu passer → pas de bascule.
     */
    public function isUncertainFailure(array $result): bool
    {
        if (!empty($result['http_status']) && (int) $result['http_status'] < 500) {
            return false; // refus explicite de la passerelle
        }
        $msg = strtolower((string) ($result['error'] ?? ''));
        foreach (['curl error 28', 'timed out', 'timeout', 'operation timed', 'connection reset', 'empty reply', 'curl error 52', 'curl error 56'] as $needle) {
            if (str_contains($msg, $needle)) {
                return true;
            }
        }
        return false;
    }

    // ── Décaissement ─────────────────────────────────────────────────

    /**
     * Décaissement mobile money avec bascule.
     * $data : reference, phone, amount, country, operator, recipient_name,
     *         first_name, last_name
     *
     * Retour : success, gateway, reference, raw_status, error, attempts[],
     *          uncertain (true = ne pas relancer sans vérification manuelle)
     */
    public function payout(array $data): array
    {
        $country  = strtoupper((string) ($data['country'] ?? ''));
        $chain    = $this->payoutChain($country);
        $attempts = [];
        $last     = null;

        if (empty($chain)) {
            return [
                'success'  => false,
                'gateway'  => null,
                'error'    => "Aucune passerelle de décaissement active pour ce pays ({$country}). Payez manuellement puis marquez le retrait comme payé.",
                'attempts' => [],
                'no_gateway' => true,
            ];
        }

        foreach ($chain as $i => $gateway) {
            $result = $gateway === 'wacepay'
                ? $this->wacepay->payout([
                    'reference'      => $data['reference'],
                    'phone'          => $data['phone'],
                    'amount'         => (int) $data['amount'],
                    'currency'       => $this->wacepay->currencyFor($country),
                    'country'        => $country,
                    'operator'       => $data['operator'] ?? 'MTN',
                    'recipient_name' => $data['recipient_name'] ?? null,
                ])
                : $this->peex->payout([
                    'reference'   => $data['reference'] . '-' . strtoupper(substr(md5((string) microtime(true)), 0, 6)),
                    'phone'       => $data['phone'],
                    'amount'      => (int) $data['amount'],
                    'currency'    => 'XAF',
                    'country'     => $country,
                    'first_name'  => $data['first_name'] ?? 'Beneficiaire',
                    'last_name'   => $data['last_name'] ?? 'TopTopGo',
                    'purpose'     => 'BUSINESS',
                    'fund_origin' => 'SALES_AND_BUSINESS_DEVELOPMENT',
                ]);

            $ok        = (bool) ($result['success'] ?? false);
            $uncertain = !$ok && $this->isUncertainFailure($result);
            $attempts[] = [
                'gateway' => $gateway,
                'role'    => $i === 0 ? 'défaut' : 'secours',
                'ok'      => $ok,
                'error'   => $ok ? null : mb_substr((string) ($result['error'] ?? 'erreur inconnue'), 0, 300),
                'at'      => now()->toIso8601String(),
            ];

            if ($ok) {
                if ($i > 0) {
                    Log::warning('Décaissement : bascule sur la passerelle de secours', ['reference' => $data['reference'], 'gateway' => $gateway, 'attempts' => $attempts]);
                }
                return [
                    'success'    => true,
                    'gateway'    => $gateway,
                    'reference'  => (string) ($result['reference'] ?? $result['transaction_id'] ?? $data['reference']),
                    'raw_status' => $result['raw_status'] ?? strtoupper((string) ($result['status'] ?? 'PENDING')),
                    'attempts'   => $attempts,
                    'fallback'   => $i > 0,
                ];
            }

            $last = $result;
            if ($uncertain) {
                Log::error('Décaissement : réponse incertaine, pas de bascule (risque de double paiement)', ['reference' => $data['reference'], 'gateway' => $gateway, 'error' => $result['error'] ?? null]);
                return [
                    'success'   => false,
                    'gateway'   => $gateway,
                    'uncertain' => true,
                    'error'     => self::label($gateway) . " n'a pas répondu à temps : vérifiez dans son tableau de bord si le paiement est parti AVANT toute nouvelle tentative.",
                    'attempts'  => $attempts,
                ];
            }
        }

        return [
            'success'  => false,
            'gateway'  => end($chain) ?: null,
            'error'    => implode(' | ', array_map(fn ($a) => self::label($a['gateway']) . ' : ' . $a['error'], $attempts)),
            'attempts' => $attempts,
        ];
    }

    public static function label(?string $gateway): string
    {
        return self::COLLECT_GATEWAYS[$gateway] ?? self::PAYOUT_GATEWAYS[$gateway] ?? ucfirst((string) $gateway);
    }
}
