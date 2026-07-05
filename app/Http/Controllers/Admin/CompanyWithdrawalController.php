<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyWithdrawal;
use App\Services\Payment\PeexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyWithdrawalController extends Controller
{
    public function __construct(protected PeexService $peex)
    {
    }

    public function index(Request $request)
    {
        $query = CompanyWithdrawal::with('company');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        $pendingCount  = CompanyWithdrawal::where('status', 'pending')->count();
        $pendingAmount = CompanyWithdrawal::where('status', 'pending')->sum('amount');

        return view('admin.company-withdrawals.index', compact('withdrawals', 'pendingCount', 'pendingAmount'));
    }

    /**
     * Approuver un retrait société : tente un virement bancaire Peex si les
     * coordonnées bancaires sont renseignées, sinon un virement mobile money
     * Peex si le pays de la société est couvert, sinon paiement manuel.
     */
    public function approve(CompanyWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $company = $withdrawal->company;
        $country = strtoupper($company->country ?? '');
        $reference = 'CWD-' . $withdrawal->id . '-' . strtoupper(Str::random(6));

        // ── 1) Virement bancaire Peex si coordonnées bancaires renseignées ──
        if (filled($company->bank_iban) && filled($company->bank_swift)) {
            $result = $this->peex->bankPayout([
                'reference'     => $reference,
                'bank_name'     => $company->bank_name,
                'bank_address'  => $company->bank_address,
                'bank_iban'     => $company->bank_iban,
                'bank_swift'    => $company->bank_swift,
                'amount'        => (int) $withdrawal->amount,
                'currency'      => 'XAF',
                'country'       => $country ?: 'CM',
                'first_name'    => $company->contact_name ?? $company->name,
                'last_name'     => $company->name,
                'purpose'       => 'BUSINESS',
                'fund_origin'   => 'SALES_AND_BUSINESS_DEVELOPMENT',
            ]);

            if ($result['success'] ?? false) {
                $withdrawal->update([
                    'status'          => 'success',
                    'method'          => 'bank',
                    'processed_at'    => now(),
                    'transaction_ref' => $result['reference'] ?? $result['transaction_id'] ?? $reference,
                ]);

                return back()->with('success', 'Retrait société payé automatiquement par virement bancaire (Peex).');
            }

            Log::warning('Peex bankPayout failed on company withdrawal, trying mobile money fallback', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $result['error'] ?? 'unknown',
            ]);
        }

        // ── 2) Virement mobile money Peex si le pays est couvert ────────────
        if ($this->peex->supportsDisbursementFor($country) && filled($company->phone)) {
            $result = $this->peex->payout([
                'reference'   => $reference,
                'phone'       => $company->phone,
                'amount'      => (int) $withdrawal->amount,
                'currency'    => 'XAF',
                'country'     => $country,
                'first_name'  => $company->contact_name ?? $company->name,
                'last_name'   => $company->name,
                'purpose'     => 'BUSINESS',
                'fund_origin' => 'SALES_AND_BUSINESS_DEVELOPMENT',
            ]);

            if ($result['success'] ?? false) {
                $withdrawal->update([
                    'status'          => 'success',
                    'method'          => 'mobile_money',
                    'processed_at'    => now(),
                    'transaction_ref' => $result['reference'] ?? $result['transaction_id'] ?? $reference,
                ]);

                return back()->with('success', 'Retrait société payé automatiquement par mobile money (Peex).');
            }

            Log::error('Peex mobile money payout failed on company withdrawal approval', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $result['error'] ?? 'unknown',
            ]);

            return back()->with('error', 'Échec du paiement Peex (banque et mobile money) : ' . ($result['error'] ?? 'erreur inconnue') . '. Le retrait reste en attente pour intervention manuelle.');
        }

        // ── 3) Ni banque ni mobile money disponibles/couverts → manuel ──────
        $withdrawal->update([
            'status'       => 'success',
            'method'       => 'manual',
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Retrait marqué comme payé (paiement manuel — aucune coordonnée Peex disponible ou pays non couvert).');
    }

    public function reject(Request $request, CompanyWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $withdrawal->update([
            'status'       => 'failed',
            'processed_at' => now(),
            'notes'        => $request->get('reason'),
        ]);

        return back()->with('success', 'Retrait société rejeté.');
    }
}
