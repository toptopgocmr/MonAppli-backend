<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Models\Driver\Driver;
use App\Models\User\User;
use App\Services\Calls\CallOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * AdminCallController — appels vocaux in-app pour le panel Admin (support).
 * Web, session Blade (admin_id) — pas de guard Eloquent dédié pour les
 * admins, comme le reste du panel (voir AdminSessionMiddleware).
 *
 * Le support est une file d'attente partagée : n'importe quel admin
 * connecté peut décrocher un appel entrant (client, chauffeur ou société),
 * pas seulement celui visé par receiver_id lors de la création du Call.
 */
class AdminCallController extends Controller
{
    public function __construct(protected CallOrchestrator $calls)
    {
    }

    private function adminId(): int
    {
        return (int) session('admin_id');
    }

    private function adminName(): string
    {
        $admin = AdminUser::find($this->adminId());
        if (!$admin) return session('admin_name', 'Support TopTopGo');
        return trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: 'Support TopTopGo';
    }

    /**
     * POST /admin/calls/initiate
     * body: { target_type: 'user'|'driver'|'company', target_id: int }
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'target_type' => 'required|in:user,driver,company',
            'target_id'   => 'required|integer',
        ]);

        $map = [
            'user'    => User::class,
            'driver'  => Driver::class,
            'company' => Company::class,
        ];
        $receiverType = $map[$request->target_type];
        $receiverId   = (int) $request->target_id;

        $exists = $receiverType::find($receiverId);
        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Destinataire introuvable.'], 404);
        }

        [$call, $agora, $alreadyActive] = $this->calls->initiate(
            AdminUser::class, $this->adminId(), $this->adminName(), '',
            $receiverType, $receiverId,
            null
        );

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
     * POST /admin/calls/{callId}/answer — n'importe quel admin connecté
     * peut décrocher un appel entrant, même si receiver_id visait un autre
     * admin (file d'attente partagée) : on utilise donc son PROPRE UID
     * Agora pour rejoindre le canal, sans exiger d'être le receiver exact.
     */
    public function answer(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        if ($call->status === 'initiated') {
            $call->update(['status' => 'answered', 'started_at' => now()]);
        }

        $agora = \App\Services\Agora\AgoraTokenService::generate(
            \App\Services\Agora\AgoraTokenService::channelForCall($call->id),
            \App\Services\Agora\AgoraTokenService::uidForAdmin($this->adminId())
        );

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * GET /admin/calls/{callId}/token
     */
    public function token(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $agora = \App\Services\Agora\AgoraTokenService::generate(
            \App\Services\Agora\AgoraTokenService::channelForCall($call->id),
            \App\Services\Agora\AgoraTokenService::uidForAdmin($this->adminId())
        );

        if (!$agora) {
            return response()->json(['success' => false, 'message' => "Service audio indisponible."], 503);
        }

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * POST /admin/calls/{callId}/end
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
     * POST /admin/calls/{callId}/missed
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
