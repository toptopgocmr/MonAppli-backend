<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverKycApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux chauffeurs',
            ], 403);
        }

        $profile = $user->driverProfile;

        if (!$profile || !$profile->isKycApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre KYC n\'est pas encore approuvé',
                'kyc_status' => $profile?->kyc_status ?? 'not_submitted',
            ], 403);
        }

        return $next($request);
    }
}
