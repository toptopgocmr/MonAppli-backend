<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Restreint une section du panel société aux agents ayant la permission
// correspondante. N'a aucun effet sur le compte principal de la société
// (toujours accès complet) ni sur le Directeur Général (accès complet).
// Usage : ->middleware('company.permission:withdrawals')
class CompanyPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        if (!CompanyContext::can($permissionKey)) {
            return redirect()->route('company.dashboard')
                ->with('error', "Vous n'avez pas accès à cette section. Contactez votre société pour obtenir l'autorisation.");
        }

        return $next($request);
    }
}
