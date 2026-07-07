<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Models\Trip;
use App\Models\User\User;
use App\Services\Calls\CallOrchestrator;
use Illuminate\Http\Request;

/**
 * UserOutboundCallController — appels du client vers le SUPPORT ou vers
 * une SOCIÉTÉ (distinct de UserCallController qui gère uniquement les
 * appels client↔chauffeur liés à une réservation).
 *
 * Les endpoints answer/end/missed/token existants de UserCallController
 * (routes /user/calls/{callId}/...) sont réutilisés tels quels ici : ils
 * n'ont jamais validé QUI est l'appelant, seulement que l'utilisateur
 * courant est bien participant du Call — donc déjà compatibles avec un
 * appel dont l'appelant est le support ou une société.
 */
class UserOutboundCallController extends Controller
{
    public function __construct(protected CallOrchestrator $calls)
    {
    }

    /**
     * POST /user/calls/outbound/initiate
     * body: { target: 'support'|'company', trip_id?: int (requis pour 'company') }
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'target'  => 'required|in:support,company',
            'trip_id' => 'required_if:target,company|nullable|integer',
        ]);

        /** @var User $user */
        $user = $request->user();
        $callerName  = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Client';
        $callerPhoto = '';
        if ($user->profile_photo) {
            $callerPhoto = str_starts_with($user->profile_photo, 'http')
                ? $user->profile_photo
                : asset('storage/' . $user->profile_photo);
        }

        if ($request->target === 'support') {
            $admin = AdminUser::first();
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Support indisponible pour le moment.'], 503);
            }

            [$call, $agora, $alreadyActive, $busy] = $this->calls->initiate(
                User::class, $user->id, $callerName, $callerPhoto,
                AdminUser::class, $admin->id,
                null
            );

            if ($busy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Toutes nos lignes client sont actuellement occupées. Veuillez réessayer dans un instant.',
                ], 503);
            }
        } else {
            $trip = Trip::with('driver')->find($request->trip_id);
            if (!$trip || !$trip->driver || !$trip->driver->company_id) {
                return response()->json(['success' => false, 'message' => 'Société introuvable pour ce trajet.'], 404);
            }

            $company = Company::find($trip->driver->company_id);
            if (!$company) {
                return response()->json(['success' => false, 'message' => 'Société introuvable.'], 404);
            }

            [$call, $agora, $alreadyActive] = $this->calls->initiate(
                User::class, $user->id, $callerName, $callerPhoto,
                Company::class, $company->id,
                $trip->id
            );
        }

        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel impossible pour le moment.'], 503);
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
            'call'    => ['id' => $call->id, 'trip_id' => $call->trip_id],
            'agora'   => $agora,
        ]);
    }
}
