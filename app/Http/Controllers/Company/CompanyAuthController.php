<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyAuthController extends Controller
{
    public function showLogin()
    {
        if (auth('company')->check() || auth('company_agent')->check()) {
            return redirect()->route('company.dashboard');
        }
        return view('company.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // ── 1) Compte principal de la société ────────────────────────────
        if (auth('company')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (auth('company')->user()->status === 'pending') {
                auth('company')->logout();
                return back()->withErrors(['email' => 'Votre compte est en attente de validation par l\'administrateur.']);
            }

            return redirect()->route('company.dashboard');
        }

        // ── 2) Agent de la société (comptable, RH, DG, flotte...) ────────
        if (auth('company_agent')->attempt($credentials, $request->boolean('remember'))) {
            $agent = auth('company_agent')->user();

            if ($agent->status !== 'active') {
                auth('company_agent')->logout();
                return back()->withErrors(['email' => 'Votre accès a été suspendu. Contactez votre société.']);
            }

            $agent->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            return redirect()->route('company.dashboard');
        }

        return back()->withErrors([
            'email' => 'Email ou mot de passe incorrect.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        auth('company')->logout();
        auth('company_agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('company.login');
    }
}
