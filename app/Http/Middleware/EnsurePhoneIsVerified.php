<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_phone_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vérifier votre numéro de téléphone',
                'requires_verification' => true,
            ], 403);
        }

        return $next($request);
    }
}
