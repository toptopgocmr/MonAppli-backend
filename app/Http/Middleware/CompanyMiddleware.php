<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ── Agent de société connecté (comptable, RH, DG, flotte...) ────────
        // On "prête" au guard 'company' le modèle Company sous-jacent, sans
        // vraie session sur ce guard, afin que tous les contrôleurs existants
        // (qui font auth('company')->user()) continuent de fonctionner tels
        // quels, que l'utilisateur courant soit la société elle-même ou un
        // de ses agents.
        if (auth('company_agent')->check()) {
            $agent = auth('company_agent')->user();

            if ($agent->status !== 'active') {
                auth('company_agent')->logout();
                return redirect()->route('company.login')
                    ->with('error', 'Votre accès agent a été suspendu. Contactez la société.');
            }

            $company = $agent->company;
            if (!$company || $company->status === 'suspended') {
                auth('company_agent')->logout();
                return redirect()->route('company.login')
                    ->with('error', 'Le compte société est suspendu.');
            }

            auth('company')->setUser($company);
            app()->instance('current.company_agent', $agent);

            return $next($request);
        }

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
