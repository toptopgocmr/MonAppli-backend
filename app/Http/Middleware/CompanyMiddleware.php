<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('company')->check()) {
            return redirect()->route('company.login');
        }

        if (auth('company')->user()->status === 'suspended') {
            auth('company')->logout();
            return redirect()->route('company.login')
                ->with('error', 'Votre compte a été suspendu. Contactez l\'administrateur.');
        }

        return $next($request);
    }
}
