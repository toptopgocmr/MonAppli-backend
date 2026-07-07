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
