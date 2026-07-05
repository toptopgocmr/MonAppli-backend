<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyWithdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    private function company()
    {
        return auth('company')->user();
    }

    public function index()
    {
        $company = $this->company();

        $availableBalance = $company->availableBalance();
        $totalNetRevenue  = $company->totalNetRevenue();

        $withdrawals = CompanyWithdrawal::where('company_id', $company->id)
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(15);

        $hasBankInfo = filled($company->bank_iban) && filled($company->bank_swift);

        return view('company.withdrawals.index', compact(
            'company', 'availableBalance', 'totalNetRevenue', 'withdrawals', 'hasBankInfo'
        ));
    }

    public function store(Request $request)
    {
        $company = $this->company();
        $available = $company->availableBalance();

        $request->validate([
            'amount' => 'required|numeric|min:1000|max:' . max(1000, $available),
        ], [
            'amount.max' => 'Le montant demandé dépasse votre solde disponible (' . number_format($available, 0, ',', ' ') . ' FCFA).',
        ]);

        if ($request->amount > $available) {
            return back()->with('error', 'Le montant demandé dépasse votre solde disponible.')->withInput();
        }

        CompanyWithdrawal::create([
            'company_id' => $company->id,
            'amount'     => $request->amount,
            'status'     => 'pending',
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
