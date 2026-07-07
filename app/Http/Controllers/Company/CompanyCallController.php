<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Services\Calls\CallOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CompanyCallController — appels vocaux in-app pour le panel Société
 * (web, session Laravel via guard "company").
 *
 * Couvre deux sens :
 *   - Société → Support : initiate() ci-dessous.
 *   - Client → Société (appel reçu) : la société répond avec answer(),
 *     raccroche avec end(), etc. — le Call a été créé côté
 *     UserOutboundCallController, la société n'est que "receiver" ici.
 *   - Support → Société (appel reçu) : idem, créé côté AdminCallController.
 */
class CompanyCallController extends Controller
{
    public function __construct(protected CallOrchestrator $calls)
    {
    }

    private function company(): Company
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        return \App\Support\CompanyContext::company();
    }

    /**
     * Résout un nom affichable pour n'importe quel type d'appelant/receveur
     * polymorphique (User, Driver, Company, AdminUser).
     */
    private function displayName(string $type, int $id): string
    {
        $model = $type::find($id);
        if (!$model) {
            return 'Inconnu (#' . $id . ')';
        }
        if ($type === Company::class) {
            return $model->name ?? 'Société #' . $id;
        }
        if ($type === AdminUser::class) {
            return trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')) ?: 'Support TopTopGo';
        }
        return trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')) ?: 'Utilisateur #' . $id;
    }

    /**
     * GET /company/calls/pending — file de secours (polling).
     * Le push Pusher (channel "company.{id}") doit normalement faire
     * sonner instantanément, mais s'il échoue silencieusement (identifiants
     * Pusher invalides côté serveur, etc.), rien ne s'affichait jamais côté
     * société — même symptôme que celui corrigé côté admin. Le widget
     * interroge cet endpoint toutes les quelques secondes en secours.
     */
    public function pending(): JsonResponse
    {
        $company = $this->company();

        $call = \App\Models\Call::where('receiver_type', Company::class)
            ->where('receiver_id', $company->id)
            ->where('status', 'initiated')
            ->orderBy('created_at')
            ->get()
            ->first(fn ($c) => !$c->isStale());

        if (!$call) {
            return response()->json(['success' => true, 'call' => null]);
        }

        return response()->json([
            'success' => true,
            'call'    => [
                'call_id'     => $call->id,
                'caller_name' => $this->displayName($call->caller_type, (int) $call->caller_id),
            ],
        ]);
    }

    /**
     * GET /company/calls — journal des appels de la société (support ↔
     * société, client ↔ société), pour voir l'historique même si la
     * sonnerie temps réel n'a jamais fonctionné.
     */
    public function index(): \Illuminate\View\View
    {
        $company = $this->company();

        $calls = \App\Models\Call::where(function ($q) use ($company) {
                $q->where('caller_type', Company::class)->where('caller_id', $company->id);
            })
            ->orWhere(function ($q) use ($company) {
                $q->where('receiver_type', Company::class)->where('receiver_id', $company->id);
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        $calls->getCollection()->transform(function ($c) use ($company) {
            $isOutbound = $c->caller_type === Company::class && (int) $c->caller_id === $company->id;
            $otherType  = $isOutbound ? $c->receiver_type : $c->caller_type;
            $otherId    = $isOutbound ? $c->receiver_id   : $c->caller_id;

            $c->direction  = $isOutbound ? 'Sortant' : 'Entrant';
            $c->other_name = $this->displayName($otherType, (int) $otherId);
            return $c;
        });

        return view('company.calls.index', compact('calls'));
    }

    /**
     * POST /company/calls/initiate — appeler le support.
     */
    public function initiate(Request $request): JsonResponse
    {
        $company = $this->company();

        $admin = AdminUser::first();
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Support indisponible pour le moment.'], 503);
        }

        [$call, $agora, $alreadyActive, $busy] = $this->calls->initiate(
            Company::class, $company->id, $company->name, $company->logo_url ?? '',
            AdminUser::class, $admin->id,
            null
        );

        if ($busy) {
            return response()->json([
                'success' => false,
                'message' => 'Toutes nos lignes société sont actuellement occupées. Veuillez réessayer dans un instant.',
            ], 503);
        }

        if ($alreadyActive) {
            return response()->json([
                'success' => false,
                'message' => 'Un appel est déjà en cours.',
                'call_id' => $call->id,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appel initié.',
            'call'    => ['id' => $call->id],
            'agora'   => $agora,
        ]);
    }

    /**
     * POST /company/calls/{callId}/answer
     */
    public function answer(Request $request, $callId): JsonResponse
    {
        $result = $this->calls->answer((int) $callId, Company::class, $this->company()->id);
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }
        if (!empty($result['already_taken'])) {
            return response()->json(['success' => false, 'message' => 'Cet appel a déjà été traité.'], 409);
        }

        return response()->json(['success' => true, 'agora' => $result['agora']]);
    }

    /**
     * GET /company/calls/{callId}/token
     */
    public function token(Request $request, $callId): JsonResponse
    {
        $result = $this->calls->token((int) $callId, Company::class, $this->company()->id);
        if (!$result || !$result['agora']) {
            return response()->json(['success' => false, 'message' => "Appel introuvable ou service audio indisponible."], 404);
        }

        return response()->json(['success' => true, 'agora' => $result['agora']]);
    }

    /**
     * POST /company/calls/{callId}/end
     */
    public function end(Request $request, $callId): JsonResponse
    {
        $call = $this->calls->end((int) $callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true, 'duration' => $call->duration_seconds]);
    }

    /**
     * POST /company/calls/{callId}/missed
     */
    public function missed(Request $request, $callId): JsonResponse
    {
        $call = $this->calls->missed((int) $callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
