<?php

namespace App\Console\Commands;

use App\Services\Payment\WacePayService;
use Illuminate\Console\Command;

/**
 * Crée (une seule fois) l'expéditeur TopTopGo chez WacePay Business à partir
 * des variables WACEPAY_SENDER_* et affiche le code à mettre dans
 * WACEPAY_SENDER_CODE.
 */
class CreateWacePaySender extends Command
{
    protected $signature = 'wacepay:create-sender';

    protected $description = 'Crée l\'expéditeur TopTopGo chez WacePay et affiche son code';

    public function handle(WacePayService $wacepay): int
    {
        $sender = (array) config('payments.wacepay.payout.sender', []);
        $missing = array_keys(array_filter($sender, fn ($v) => $v === null || $v === ''));
        if ($missing) {
            $this->error('Variables manquantes pour : ' . implode(', ', $missing));
            return self::FAILURE;
        }

        $res = $wacepay->createSender(array_merge($sender, [
            'dateOfBirth'  => substr((string) $sender['dateOfBirth'], 0, 10),
            'dateExpireId' => substr((string) $sender['dateExpireId'], 0, 10),
        ]));

        if (!$res['success'] || !$res['code']) {
            $this->error('Échec : ' . ($res['error'] ?? 'code absent'));
            $this->line(json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }

        $this->info('Expéditeur créé. Ajoutez dans les variables Railway :');
        $this->line('WACEPAY_SENDER_CODE=' . $res['code']);

        return self::SUCCESS;
    }
}
