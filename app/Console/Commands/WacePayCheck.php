<?php

namespace App\Console\Commands;

use App\Services\Payment\WacePayService;
use Illuminate\Console\Command;

/**
 * Diagnostic WacePay (plateforme PayIn : collecte + décaissement).
 *   php artisan wacepay:check
 * Affiche : connexion (jeton), URL de callback, comptes payout (soldes par
 * devise) et services de décaissement souscrits (subscriptionId, pays, devise,
 * frais, min/max) — à recopier si besoin dans WACEPAY_PAYOUT_SUBSCRIPTIONS.
 */
class WacePayCheck extends Command
{
    protected $signature = 'wacepay:check';

    protected $description = 'Teste les accès WacePay et liste les comptes / services de décaissement';

    public function handle(WacePayService $wacepay): int
    {
        if (!$wacepay->isCollectConfigured()) {
            $this->error('WacePay non configuré : WACEPAY_PAYIN_BASE_URL / PUBLIC_KEY / PRIVATE_KEY / SUBSCRIPTION_KEY.');
            return self::FAILURE;
        }

        $this->info('— Connexion');
        $st = $wacepay->getTransactionStatus('TTG-DIAG-' . now()->timestamp);
        $authOk = ($st['success'] ?? false) || in_array($st['http_status'] ?? 0, [400, 404, 422], true);
        $this->line($authOk ? 'OK (jeton obtenu)' : 'ÉCHEC : ' . ($st['error'] ?? '?') . ' — vérifiez les clés et que l\'IP du serveur est autorisée.');
        $this->line('Callback collecte : ' . preg_replace('/token=[^&]+/', 'token=***', $wacepay->callbackUrl()));

        $this->info("\n— Comptes de décaissement (soldes)");
        $acc = $wacepay->payoutAccount();
        $this->line($acc['ok'] ? json_encode($acc['json']['data'] ?? $acc['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '→ ' . $acc['error']);

        $this->info("\n— Services de décaissement souscrits");
        $services = $wacepay->payoutServices();
        if (!$services) {
            $this->warn('Aucun service (souscrivez les services Payout dans le tableau de bord WacePay).');
            return self::SUCCESS;
        }

        $this->table(
            ['subscriptionId', 'Service', 'Pays', 'Devise', 'Frais fixes', 'Taux %', 'Min', 'Max', 'Actif'],
            array_map(fn ($s) => [
                $s['subscriptionId'] ?? '?', $s['name'] ?? '?', $s['countryCode'] ?? $s['country'] ?? '?',
                $s['currency'] ?? '?', $s['fixedFee'] ?? '', $s['rate'] ?? '', $s['minAmount'] ?? '',
                $s['maxAmount'] ?? '', isset($s['active']) ? ($s['active'] ? 'oui' : 'non') : '?',
            ], $services)
        );

        return self::SUCCESS;
    }
}
