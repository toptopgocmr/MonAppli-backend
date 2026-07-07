<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Driver\Driver;
use App\Services\Calls\CallOrchestrator;
use Illuminate\Http\Request;

/**
 * DriverSupportCallController — appel du chauffeur vers le SUPPORT
 * (distinct de DriverCallController qui gère les appels chauffeur↔client
 * liés à un trajet).
 *
 * Les endpoints answer/end/missed/token existants de DriverCallController
 * (routes /driver/calls/{callId}/...) sont réutilisés tels quels : ils ne
 * valident que la participation du chauffeur au Call, pas qui a appelé.
 */
class DriverSupportCallController extends Controller
{
    public function __construct(protected CallOrchestrator $calls)
    {
    }

    /**
     * POST /driver/calls/support/initiate
     */
    public function initiate(Request $request)
    {
        /** @var Driver $driver */
        $driver = $request->user();

        $admin = AdminUser::first();
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Support indisponible pour le moment.'], 503);
        }

        $callerName  = trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')) ?: 'Chauffeur';
        $callerPhoto = '';
        if (!empty($driver->profile_photo)) {
            $callerPhoto = str_starts_with($driver->profile_photo, 'http')
                ? $driver->profile_photo
                : asset('storage/' . $driver->profile_photo);
        }

        [$call, $agora, $alreadyActive, $busy] = $this->calls->initiate(
            Driver::class, $driver->id, $callerName, $callerPhoto,
            AdminUser::class, $admin->id,
            null
        );

        if ($busy) {
            return response()->json([
                'success' => false,
                'message' => 'Toutes nos lignes chauffeur sont actuellement occupées. Veuillez réessayer dans un instant.',
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
}
