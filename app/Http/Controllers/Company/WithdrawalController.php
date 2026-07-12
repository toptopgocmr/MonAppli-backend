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

    // Pays vers lesquels un retrait peut être effectué (couverts par Peex Disbursement/Remittance).
    // Enrichi avec indicatif/opérateurs (config/mobile_money.php, miroir de
    // kCountries côté app mobile) + une URL de drapeau (flagcdn.com — les
    // drapeaux emoji ne s'affichent pas correctement sous Windows/Chrome,
    // qui montre le code pays en boîte au lieu du drapeau).
    private function payoutCountries(): array
    {
        $codes = config('payments.peex.disbursement_countries', ['CM', 'CG']);
        $all   = config('geo.countries', []);
        $meta  = config('mobile_money.countries', []);

        return collect($all)
            ->filter(fn ($c) => in_array($c['code'], $codes, true))
            ->map(function ($c) use ($meta) {
                $m = $meta[$c['code']] ?? null;
                $c['dial']      = $m['dial'] ?? '';
                $c['operators'] = $m['operators'] ?? [];
                $c['flag_url']  = 'https://flagcdn.com/w40/' . strtolower($c['code']) . '.png';
                return $c;
            })
            ->values()
            ->all();
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

    public function store(Request $request)
    {
        $company = $this->company();
        $available = $company->availableBalance();
        $allowedCountryCodes = collect($this->payoutCountries())->pluck('code')->all();

        $request->validate([
            'amount'       => 'required|numeric|min:1000|max:' . max(1000, $available),
            'method'       => 'required|in:mobile_money,bank',
            'country'      => 'required|in:' . implode(',', $allowedCountryCodes ?: ['CM']),
            'operator'     => 'required_if:method,mobile_money|nullable|string|max:60',
            'phone_number' => 'required_if:method,mobile_money|nullable|string|max:20',
        ], [
            'amount.max' => 'Le montant demandé dépasse votre solde disponible (' . number_format($available, 0, ',', ' ') . ' FCFA).',
        ]);

        if ($request->amount > $available) {
            return back()->with('error', 'Le montant demandé dépasse votre solde disponible.')->withInput();
        }

        if ($request->method === 'bank' && !(filled($company->bank_iban) && filled($company->bank_swift))) {
            return back()->with('error', 'Renseignez vos coordonnées bancaires avant de demander un retrait par virement bancaire.')->withInput();
        }

        CompanyWithdrawal::create([
            'company_id'   => $company->id,
            'amount'       => $request->amount,
            'method'       => $request->method,
            'country'      => $request->country,
            'operator'     => $request->method === 'mobile_money' ? $request->operator : null,
            'phone_number' => $request->method === 'mobile_money' ? $request->phone_number : null,
            'status'       => 'pending',
        ]);

        return redirect()->route('company.withdrawals.index')
                         ->with('success', 'Demande de retrait envoyée. Elle sera traitée par l\'administration.');
    }

    // ── Coordonnées bancaires (pour virement Peex) ──────────────────
    public function updateBankInfo(Request $request)
    {
        $company = $this->company();

        $request->validate([
            'bank_name'    => 'nullable|string|max:150',
            'bank_iban'    => 'nullable|string|max:50',
            'bank_swift'   => 'nullable|string|max:20',
            'bank_address' => 'nullable|string|max:255',
        ]);

        $company->update($request->only(['bank_name', 'bank_iban', 'bank_swift', 'bank_address']));

        return redirect()->route('company.withdrawals.index')
                         ->with('success', 'Coordonnées bancaires mises à jour.');
    }
}
