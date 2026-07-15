<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;

class DriverWithdrawalController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request)
    {
        $withdrawals = Withdrawal::where('driver_id', $request->user()->id)
                                 ->latest()
                                 ->paginate(20);

        return response()->json(['success' => true, 'data' => $withdrawals]);
    }

    /**
     * ✅ FIX : cet endpoint ne correspondait ni au payload réellement envoyé
     * par l'app chauffeur (wallet_page.dart) ni au schéma de la table
     * `withdrawals` — TOUTE demande de retrait échouait donc silencieusement :
     *   - L'app envoie le téléphone sous la clé `phone`, pas `phone_number`
     *     (champ marqué "required" ici → 422 systématique).
     *   - `method` valait `mobile_money`/`card` côté app, mais devait valoir
     *     `mobile_money`/`bank` ici, et la colonne DB `method` était en plus
     *     un ENUM('mtn','orange','airtel','moov') — aucune de ces valeurs ne
     *     matchait, ce qui aurait de toute façon fait planter l'INSERT.
     * On accepte maintenant le payload réel : `method` (mobile_money|card),
     * `operator`/`network` (nom de l'opérateur, ex: "MTN Congo") et `phone`.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'method' => 'required|string|in:mobile_money,card',
        ]);

        // ⚠️ Retrait par carte bancaire : pas encore supporté côté serveur.
        // On refuse explicitement plutôt que de stocker un numéro de carte
        // en clair (aucun coffre-fort/tokenisation PCI-DSS en place) — voir
        // remarque envoyée séparément sur cet onglet de l'app.
        if ($request->method === 'card') {
            return response()->json([
                'success' => false,
                'message' => 'Le retrait par carte bancaire n\'est pas encore disponible. Utilisez Mobile Money.',
            ], 422);
        }

        $request->validate([
            'phone' => 'required|string|min:6',
        ]);

        $operator = trim((string) ($request->operator ?: $request->network ?: 'mobile_money'));

        $withdrawal = $this->walletService->requestWithdrawal(
            $request->user()->id,
            $request->amount,
            $operator,
            $request->phone
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Demande de retrait soumise avec succès.',
            'data'       => $withdrawal,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $withdrawal = Withdrawal::where('id', $id)
                                ->where('driver_id', $request->user()->id)
                                ->firstOrFail();

        return response()->json(['success' => true, 'data' => $withdrawal]);
    }
}