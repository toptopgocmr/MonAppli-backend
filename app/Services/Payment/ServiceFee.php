<?php

namespace App\Services\Payment;

use App\Models\PaymentGatewaySetting;

/**
 * Frais de service TopTopGo payés par le CLIENT, en plus du prix de la course.
 * Ils couvrent les frais du partenaire de paiement (Peex, Digitwace…) et la
 * marge TopTopGo. La commission sur le chauffeur reste gérée à part
 * (Commissions / taux société).
 *
 * Réglages (admin › Finances › Passerelles de paiement › Frais de service) :
 *   service_fee_enabled  1|0      (défaut 1)
 *   service_fee_percent  ex. 5    (% du prix de la course, défaut 5)
 *   service_fee_min      ex. 100  (XAF, défaut 100)
 *   service_fee_max      ex. 0    (XAF, 0 = pas de plafond)
 */
class ServiceFee
{
    public static function settings(): array
    {
        $g = fn (string $k, $d) => PaymentGatewaySetting::getValue($k, $d);

        return [
            'enabled' => filter_var($g('service_fee_enabled', config('payments.service_fee.enabled', true)), FILTER_VALIDATE_BOOLEAN),
            'percent' => max(0.0, (float) $g('service_fee_percent', config('payments.service_fee.percent', 5))),
            'min'     => max(0.0, (float) $g('service_fee_min', config('payments.service_fee.min', 100))),
            'max'     => max(0.0, (float) $g('service_fee_max', config('payments.service_fee.max', 0))),
        ];
    }

    /** Frais de service (XAF, entier arrondi au supérieur) pour un prix de course donné. */
    public static function for(float $courseAmount): float
    {
        $s = self::settings();
        if (!$s['enabled'] || $courseAmount <= 0) {
            return 0.0;
        }

        $fee = ceil($courseAmount * $s['percent'] / 100);
        $fee = max($fee, $s['min']);
        if ($s['max'] > 0) {
            $fee = min($fee, $s['max']);
        }
        return (float) $fee;
    }

    /** Détail complet : course, frais, total payé par le client. */
    public static function breakdown(float $courseAmount): array
    {
        $fee = self::for($courseAmount);
        return [
            'course_amount' => round($courseAmount, 2),
            'service_fee'   => $fee,
            'total_to_pay'  => round($courseAmount + $fee, 2),
        ];
    }
}
