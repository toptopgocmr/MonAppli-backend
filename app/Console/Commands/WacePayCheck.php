<?php

namespace App\Console\Commands;

use App\Services\Payment\WacePayService;
use Illuminate\Console\Command;

/**
 * Diagnostic WacePay : connexion PayIn, solde Business, listes A/B/C
 * (origine des fonds, motifs, relations) et services de décaissement d'un pays.
 *   php artisan wacepay:check --country=CG
 */
class WacePayCheck extends Command
{
    protected $signature = 'wacepay:check {--country=CG} {--currency=XAF}';

    protected $description = 'Teste les accès WacePay et affiche les valeurs acceptées (listes, services)';

    public function handle(WacePayService $wacepay): int
    {
        $country = strtoupper($this->option('country'));

        $this->info('— Collecte (PayIn)');
        if ($wacepay->isCollectConfigured()) {
            $st = $wacepay->getTransactionStatus('TTG-DIAG-' . now()->timestamp);
            $this->line('Connexion : ' . (($st['success'] ?? false) || ($st['http_status'] ?? 0) === 404 ? 'OK' : ($st['error'] ?? 'échec')));
            $this->line('Callback  : ' . preg_replace('/token=[^&]+/', 'token=***', $wacepay->callbackUrl()));
        } else {
            $this->warn('Non configurée (WACEPAY_PAYIN_*)');
        }

        $this->info('— Décaissement (Business)');
        if (!config('payments.wacepay.payout.base_url') || !config('payments.wacepay.payout.email')) {
            $this->warn('Non configuré (WACEPAY_PAYOUT_*)');
            return self::SUCCESS;
        }

        $calls = [
            'Solde'                    => ['GET', 'account/balance', []],
            'Liste A – origine fonds'  => ['GET', 'transaction/origin_fund/b2B', []],
            'Liste B – motifs'         => ['GET', 'transaction/reason/b2B', []],
            'Liste C – relations'      => ['GET', 'transaction/relation', []],
            "Services $country"        => ['POST', 'transaction/payoutservice', ['payoutCountry' => $country, 'payoutCurrency' => $this->option('currency')]],
        ];

        foreach ($calls as $label => [$method, $path, $body]) {
            $res = $wacepay->business($method, $path, $body);
            $this->line("\n<comment>$label</comment> " . ($res['ok'] ? '' : '→ ' . $res['error']));
            $this->line(mb_substr(json_encode($res['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 3000));
        }

        return self::SUCCESS;
    }
}
