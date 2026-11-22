<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyWithdrawal;
use App\Services\Payment\PeexService;
use App\Services\Payment\WacePayService;
use App\Services\Payment\GatewayRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyWithdrawalController extends Controller
{
    public function __construct(
        protected PeexService    $peex,
        protected WacePayService $wacepay,
        protected GatewayRouter  $gateways,
    ) {
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

        // ✅ Récap CA brut / commission TopTopGo / solde réel PAR société, pour
        // que l'admin voie d'un coup d'œil si le montant demandé est cohérent
        // avec le solde réellement disponible avant d'approuver.
        $recapByCompany = [];
        foreach ($withdrawals->getCollection()->pluck('company')->filter()->unique('id') as $company) {
            $recapByCompany[$company->id] = $company->withdrawalRecap();
        }

        return view('admin.company-withdrawals.index', compact(
            'withdrawals', 'pendingCount', 'pendingAmount', 'recapByCompany'
        ));
    }

    /**
     * Approuver un retrait société : utilise le moyen de paiement (banque ou
     * mobile money) et le pays explicitement choisis par la société lors de
     * sa demande. Aucun changement automatique de méthode n'est effectué —
     * si le paiement Peex échoue, le retrait reste en attente pour
     * intervention manuelle.
     */
    public function approve(CompanyWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut plus être modifié.');
        }

        $company = $withdrawal->company;
        $country = strtoupper($withdrawal->country ?: $company->country ?? '') ?: 'CM';
        $reference = 'CWD-' . $withdrawal->id . '-' . strtoupper(Str::random(6));

        // ── Virement bancaire : uniquement si la société a choisi "bank" ────
        if ($withdrawal->method === 'bank') {
            if (!(filled($company->bank_iban) && filled($company->bank_swift))) {
                return back()->with('error', 'Coordonnées bancaires manquantes pour cette société. Impossible de traiter ce retrait par virement bancaire.');
            }

            if (!$this->peex->supportsBankFor($country)) {
                return back()->with('error', "Le pays choisi ($country) n'est pas couvert pour le virement bancaire.");
            }

            $result = $this->peex->bankPayout([
                'reference'     => $reference,
                'bank_name'     => $company->bank_name,
                'bank_address'  => $company->bank_address,
                'bank_iban'     => $company->bank_iban,
                'bank_swift'    => $company->bank_swift,
                'amount'        => (int) $withdrawal->amount,
                'currency'      => 'XAF',
                'country'       => $country,
                'first_name'    => $company->contact_name ?? $company->name,
                'last_name'     => $company->name,
                'purpose'       => 'BUSINESS',
                'fund_origin'   => 'SALES_AND_BUSINESS_DEVELOPMENT',
            ]);

            if ($result['success'] ?? false) {
                $withdrawal->update([
                    'status'          => 'success',
                    'processed_at'    => now(),
                    'transaction_ref' => $result['reference'] ?? $result['transaction_id'] ?? $reference,
                ]);

                return back()->with('success', 'Retrait société payé automatiquement par virement bancaire (Peex).');
            }

            Log::error('Peex bankPayout failed on company withdrawal approval', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $result['error'] ?? 'unknown',
            ]);

            return back()->with('error', 'Échec du virement bancaire Peex : ' . ($result['error'] ?? 'erreur inconnue') . '. Le retrait reste en attente pour intervention manuelle.');
        }

        // ── Mobile money : uniquement si la société a choisi "mobile_money" ─
        if ($withdrawal->method === 'mobile_money') {
            if (!filled($withdrawal->phone_number)) {
                return back()->with('error', 'Numéro mobile money manquant sur cette demande de retrait.');
            }

            if (filled($withdrawal->transaction_ref) && in_array($withdrawal->provider, ['wacepay', 'peex'], true)) {
                return back()->with('error', 'Une transaction ' . GatewayRouter::label($withdrawal->provider) . ' existe déjà pour ce retrait (réf. ' . $withdrawal->transaction_ref . '). Vérifiez-la avant toute nouvelle action.');
            }

            // Passerelle par défaut puis secours (Finances › Passerelles de paiement).
            $result = $this->gateways->payout([
                'reference'      => $reference,
                'phone'          => $withdrawal->phone_number,
                'amount'         => (int) $withdrawal->amount,
                'country'        => $country,
                'operator'       => $withdrawal->operator ?: 'MTN',
                'recipient_name' => $company->contact_name ?: $company->name,
                'first_name'     => $company->contact_name ?? $company->name,
                'last_name'      => $company->name,
            ]);

            $withdrawal->update([
                'gateway_attempts' => array_merge((array) ($withdrawal->gateway_attempts ?? []), $result['attempts'] ?? []),
            ]);

            if ($result['success'] ?? false) {
                $withdrawal->update([
                    'status'          => 'success',
                    'processed_at'    => now(),
                    'transaction_ref' => $result['reference'],
                    'provider'        => $result['gateway'],
                    'provider_status' => $result['raw_status'] ?? 'PENDING',
                ]);

                $via = GatewayRouter::label($result['gateway']) . (!empty($result['fallback']) ? ' (passerelle de secours)' : '');
                return back()->with('success', "Retrait société envoyé via {$via} — réf. {$result['reference']}.");
            }

            if (!empty($result['uncertain'])) {
                $withdrawal->update(['provider' => $result['gateway'] ?? null, 'provider_status' => 'UNCERTAIN']);
            }

            Log::error('Retrait société : décaissement mobile money échoué', [
                'withdrawal_id' => $withdrawal->id,
                'country'       => $country,
                'error'         => $result['error'] ?? 'unknown',
            ]);

            return back()->with('error', 'Retrait non payé : ' . ($result['error'] ?? 'erreur inconnue') . ' Le retrait reste en attente.');
        }

        // ── Méthode inconnue (anciennes demandes) : rien n'est payé
        // automatiquement, et on ne marque plus « payé » sans paiement réel.
        return back()->with('error', 'Méthode de retrait non reconnue sur cette demande : payez la société hors plateforme puis rejetez/régularisez la demande.');
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
