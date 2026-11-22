<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\CompanyWithdrawal;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Withdrawal;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\GatewayRouter;
use App\Services\Payment\NotchPayService;
use App\Services\Payment\PeexService;
use App\Services\Payment\ServiceFee;
use App\Services\Payment\WacePayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Finances › Passerelles de paiement : passerelle par défaut + secours
 * (collecte et décaissement), état de chaque passerelle et dernières bascules.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(
        protected GatewayRouter      $router,
        protected WacePayService     $wacepay,
        protected PeexService        $peex,
        protected NotchPayService    $notchpay,
        protected FlutterwaveService $flutterwave,
    ) {
    }

    public function index()
    {
        $settings = $this->router->settings();

        $up = fn ($v) => array_values(array_map('strtoupper', array_filter((array) $v)));

        $gateways = [
            'wacepay' => [
                'collect_ok'        => $this->wacepay->isCollectConfigured(),
                'payout_ok'         => $this->wacepay->isPayoutConfigured(),
                'collect_countries' => $up(config('payments.wacepay.payin.countries')),
                'payout_countries'  => $up(config('payments.wacepay.payout.countries')),
                'hint'              => 'Clés WACEPAY_PAYIN_* ; IP du serveur à autoriser dans le tableau de bord WacePay.',
            ],
            'peex' => [
                'collect_ok'        => $this->peex->isAvailable(),
                'payout_ok'         => $this->peex->isAvailable(),
                'collect_countries' => $up($this->peex->supportedCollectCountries()),
                'payout_countries'  => $up($this->peex->supportedDisbursementCountries()),
                'hint'              => 'Mode ' . (config('payments.peex.sandbox', true) ? 'SANDBOX' : 'PRODUCTION') . ' — URL : '
                    . (config('payments.peex.sandbox', true) ? config('payments.peex.base_url') : config('payments.peex.production_url'))
                    . ' (l\'URL de production est communiquée par Peex avec les accès de production).',
            ],
            'notchpay' => [
                'collect_ok'        => $this->notchpay->isAvailable(),
                'payout_ok'         => false,
                'collect_countries' => $up(config('payments.notchpay.collect_countries')),
                'payout_countries'  => [],
                'hint'              => 'Collecte uniquement (NOTCHPAY_PUBLIC_KEY).',
            ],
            'flutterwave' => [
                'collect_ok'        => $this->flutterwave->isAvailable(),
                'payout_ok'         => false,
                'collect_countries' => ['Tous pays Flutterwave'],
                'payout_countries'  => [],
                'hint'              => 'Collecte uniquement, utilisée quand aucune autre passerelle ne couvre le pays.',
            ],
        ];

        // Aperçu de l'ordre réellement appliqué pour les pays principaux.
        $preview = [];
        foreach (['CG', 'CM', 'GA', 'CI', 'SN'] as $c) {
            $preview[$c] = [
                'collect' => $this->router->collectChain($c),
                'payout'  => $this->router->payoutChain($c),
            ];
        }

        // Dernières bascules (plus d'une tentative) et derniers échecs.
        $events = collect();
        $pull = function ($rows, string $type) use (&$events) {
            foreach ($rows as $r) {
                $att = (array) ($r->gateway_attempts ?? []);
                if (count($att) > 1 || (count($att) === 1 && empty($att[0]['ok']))) {
                    $events->push([
                        'type'     => $type,
                        'id'       => $r->id,
                        'amount'   => $r->amount,
                        'status'   => $r->status,
                        'attempts' => $att,
                        'date'     => $r->updated_at,
                    ]);
                }
            }
        };
        $pull(Payment::whereNotNull('gateway_attempts')->latest('updated_at')->limit(200)->get(), 'Collecte');
        $pull(Withdrawal::whereNotNull('gateway_attempts')->latest('updated_at')->limit(100)->get(), 'Retrait chauffeur');
        $pull(CompanyWithdrawal::whereNotNull('gateway_attempts')->latest('updated_at')->limit(100)->get(), 'Retrait société');
        $events = $events->sortByDesc('date')->take(25)->values();

        // Volumes des 30 derniers jours par passerelle (collecte).
        $stats = Payment::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('provider')
            ->selectRaw('provider, status, COUNT(*) as n, SUM(amount) as total')
            ->groupBy('provider', 'status')
            ->get()
            ->groupBy('provider');

        return view('admin.payment-gateways.index', [
            'settings'        => $settings,
            'gateways'        => $gateways,
            'collectOptions'  => GatewayRouter::COLLECT_GATEWAYS,
            'payoutOptions'   => GatewayRouter::PAYOUT_GATEWAYS,
            'preview'         => $preview,
            'events'          => $events,
            'stats'           => $stats,
            'fee'             => ServiceFee::settings(),
            'feeExamples'     => array_map(fn ($a) => ServiceFee::breakdown($a), [500, 1000, 2500, 5000, 10000, 25000]),
        ]);
    }

    public function update(Request $request)
    {
        $collect = implode(',', array_keys(GatewayRouter::COLLECT_GATEWAYS));
        $payout  = implode(',', array_keys(GatewayRouter::PAYOUT_GATEWAYS));

        $data = $request->validate([
            'collect_primary'  => "required|in:$collect",
            'collect_fallback' => "nullable|in:$collect|different:collect_primary",
            'payout_primary'   => "required|in:$payout",
            'payout_fallback'  => "nullable|in:$payout|different:payout_primary",
            'service_fee_percent' => 'required|numeric|min:0|max:50',
            'service_fee_min'     => 'required|numeric|min:0|max:100000',
            'service_fee_max'     => 'nullable|numeric|min:0|max:1000000',
        ], [
            'collect_fallback.different' => 'La passerelle de secours (collecte) doit être différente de la passerelle par défaut.',
            'payout_fallback.different'  => 'La passerelle de secours (décaissement) doit être différente de la passerelle par défaut.',
        ]);

        $old     = $this->router->settings() + ['service_fee' => ServiceFee::settings()];
        $adminId = (int) session('admin_id') ?: null;

        PaymentGatewaySetting::setValue('collect_primary', $data['collect_primary'], $adminId);
        PaymentGatewaySetting::setValue('collect_fallback', $data['collect_fallback'] ?? '', $adminId);
        PaymentGatewaySetting::setValue('collect_auto', $request->boolean('collect_auto') ? '1' : '0', $adminId);
        PaymentGatewaySetting::setValue('payout_primary', $data['payout_primary'], $adminId);
        PaymentGatewaySetting::setValue('payout_fallback', $data['payout_fallback'] ?? '', $adminId);
        PaymentGatewaySetting::setValue('payout_auto', $request->boolean('payout_auto') ? '1' : '0', $adminId);

        PaymentGatewaySetting::setValue('service_fee_enabled', $request->boolean('service_fee_enabled') ? '1' : '0', $adminId);
        PaymentGatewaySetting::setValue('service_fee_percent', (string) (float) $data['service_fee_percent'], $adminId);
        PaymentGatewaySetting::setValue('service_fee_min', (string) (float) $data['service_fee_min'], $adminId);
        PaymentGatewaySetting::setValue('service_fee_max', (string) (float) ($data['service_fee_max'] ?? 0), $adminId);

        $new = $this->router->settings() + ['service_fee' => ServiceFee::settings()];

        try {
            AdminLog::create([
                'admin_id'   => $adminId,
                'action'     => 'update_payment_gateways',
                'model'      => 'PaymentGatewaySetting',
                'model_id'   => null,
                'old_data'   => $old,
                'new_data'   => $new,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AdminLog passerelles non enregistré : ' . $e->getMessage());
        }

        return back()->with('success', 'Passerelles de paiement enregistrées.');
    }

    /** Test de connexion rapide (aucune transaction n'est créée). */
    public function test(string $gateway)
    {
        $label = GatewayRouter::label($gateway);

        $result = match ($gateway) {
            'wacepay' => (function () {
                if (!$this->wacepay->isCollectConfigured()) {
                    return [false, 'clés WACEPAY_PAYIN_* manquantes.'];
                }
                $acc = $this->wacepay->payoutAccount();
                $services = $this->wacepay->payoutServices();
                return $acc['ok']
                    ? [true, 'connexion OK — ' . count($services) . ' service(s) de décaissement souscrit(s).']
                    : [false, $acc['error'] ?? 'échec'];
            })(),
            'peex' => (function () {
                $r = $this->peex->getPartnerInfo();
                if (!($r['success'] ?? false)) {
                    return [false, $r['error'] ?? 'échec'];
                }
                $solde = data_get($r, 'data.collect_solde') ?? data_get($r, 'data.data.collect_solde');
                return [true, 'connexion OK' . ($solde !== null ? " — solde collecte : {$solde}" : '') . '.'];
            })(),
            'notchpay'    => [$this->notchpay->isAvailable(), $this->notchpay->isAvailable() ? 'clé configurée (pas de test en ligne).' : 'clé NOTCHPAY_PUBLIC_KEY manquante.'],
            'flutterwave' => [$this->flutterwave->isAvailable(), $this->flutterwave->isAvailable() ? 'clés configurées (pas de test en ligne).' : 'clés FLW_* manquantes.'],
            default       => [false, 'passerelle inconnue.'],
        };

        return back()->with($result[0] ? 'success' : 'error', "{$label} : {$result[1]}");
    }
}
