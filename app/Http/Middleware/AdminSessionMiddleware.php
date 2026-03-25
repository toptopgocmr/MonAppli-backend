<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSessionMiddleware
{
    /**
     * Vérifie qu'un admin est connecté via session Blade.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Veuillez vous connecter.']);
        }

        return $next($request);
    }
}