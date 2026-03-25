<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Admin\AdminUser;
use App\Models\AdminLog;

class AdminAuthController extends Controller
{
    /**
     * Login — gère Blade (session) ET API (token)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Récupérer l'admin avec rôle et permissions
        $admin = AdminUser::with('role.permissions')
            ->where('email', $request->email)
            ->first();

        // Vérifier email et mot de passe
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            // Si requête JSON (API), retourner JSON
            if ($request->expectsJson()) {
                throw ValidationException::withMessages([
                    'email' => ['Identifiants incorrects.'],
                ]);
            }

            // Si formulaire Blade, retourner avec erreur
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->withInput($request->only('email'));
        }

        // Vérifier le statut
        if ($admin->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Compte désactivé.'], 403);
            }
            return back()->withErrors([
                'email' => 'Votre compte est désactivé.',
            ]);
        }

        // Logger la connexion
        AdminLog::create([
            'admin_id'   => $admin->id,
            'action'     => 'login',
            'ip_address' => $request->ip(),
        ]);

        // -------------------------------------------------------
        // Si requête API (Postman, mobile...) → retourner token
        // -------------------------------------------------------
        if ($request->expectsJson()) {
            $admin->tokens()->delete();
            $token = $admin->createToken('admin-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'admin' => $admin->load('role.permissions'),
            ]);
        }

        // -------------------------------------------------------
        // Si formulaire Blade → stocker en session et rediriger
        // -------------------------------------------------------
        session([
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->first_name . ' ' . $admin->last_name,
            'admin_email' => $admin->email,
            'admin_role'  => $admin->role_id,
        ]);

        return redirect()->route('admin.dashboard');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        // Si connecté via API token
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            AdminLog::create([
                'admin_id'   => $request->user()->id,
                'action'     => 'logout',
                'ip_address' => $request->ip(),
            ]);
        }

        // Vider la session Blade
        session()->forget(['admin_id', 'admin_name', 'admin_email', 'admin_role']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Déconnecté avec succès.']);
        }

        return redirect()->route('admin.login');
    }

    /**
     * Current admin info (API)
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        return response()->json($user->load('role.permissions'));
    }
}