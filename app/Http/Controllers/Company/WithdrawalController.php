<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyWithdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    private function company()
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        return \App\Support\CompanyContext::company();
    }

    // Pays vers lesquels un retrait peut être effectué.
    // ⚠️ IMPORTANT : Peex expose 2 mécanismes différents avec des couvertures
    // pays DIFFÉRENTES (vérifié sur https://peex-api-docs.peexit.com/) :
    //   - Disbursement API (mobile money) : "Currently, the only active
    //     country for disbursement is Cameroon (CM)." → CG ajouté par
    //     confirmation écrite de Peex. Zone volontairement restreinte.
    //   - Remittance API / virement bancaire (request_bank_payment) :
    //     aucune restriction de pays documentée → on autorise toute la zone
    //     CEMAC (payments.peex.bank_countries).
    // Chaque pays porte donc 2 flags (mobile_money_ok / bank_ok) : le
    // formulaire (JS) et la validation store() filtrent la liste affichée
    // selon le moyen de paiement choisi pour ne jamais soumettre à Peex un
    // couple pays/méthode qu'il rejettera.
    private function payoutCountries(): array
    {
        $mmCodes   = config('payments.peex.disbursement_countries', ['CM']);
        $bankCodes = config('payments.peex.bank_countries', ['CM']);
        $allCodes  = array_unique(array_merge($mmCodes, $bankCodes));
        $all   = config('geo.countries', []);
        $meta  = config('mobile_money.countries', []);

        return collect($all)
            ->filter(fn ($c) => in_array($c['code'], $allCodes, true))
            ->map(function ($c) use ($meta, $mmCodes, $bankCodes) {
                $m = $meta[$c['code']] ?? null;
                $c['dial']             = $m['dial'] ?? '';
                $c['operators']        = collect($m['operators'] ?? [])
                    ->map(function ($op) {
                        $op['logo'] = $this->operatorLogo($op['name'] ?? '');
                        return $op;
                    })->all();
                $c['flag_url']         = 'https://flagcdn.com/w40/' . strtolower($c['code']) . '.png';
                $c['mobile_money_ok']  = in_array($c['code'], $mmCodes, true);
                $c['bank_ok']          = in_array($c['code'], $bankCodes, true);
                return $c;
            })
            ->values()
            ->all();
    }

    // ✅ Vrai logo (fichier local public/images/operators/) pour les 3
    // opérateurs demandés par le client (Airtel/Orange/MTN) — les autres
    // (Vodacom, Africell, Moov, Telecel...) restent sur le badge coloré
    // avec initiales (voir renderOperatorChips() dans la vue).
    private function operatorLogo(string $operatorName): ?string
    {
        $n = strtolower($operatorName);
        if (str_contains($n, 'airtel')) return asset('images/operators/airtel.png');
        if (str_contains($n, 'orange')) return asset('images/operators/orange.png');
        if (str_contains($n, 'mtn') || str_contains($n, 'mobicash')) return asset('images/operators/mtn.png');
        return null;
    }

    public function index()
    {
        $company = $this->company();

        $availableBalance = $company->availableBalance();
        $totalNetRevenue  = $company->totalNetRevenue();
        $recap            = $company->withdrawalRecap();

        $withdrawals = CompanyWithdrawal::where('company_id', $company->id)
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(15);

        $hasBankInfo = filled($company->bank_iban) && filled($company->bank_swift);
        $payoutCountries = $this->payoutCountries();

        return view('company.withdrawals.index', compact(
            'company', 'availableBalance', 'totalNetRevenue', 'recap', 'withdrawals', 'hasBankInfo', 'payoutCountries'
        ));
    }

    public function store(Request $