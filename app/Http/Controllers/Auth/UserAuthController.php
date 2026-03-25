<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserAuthController extends Controller
{
    // ── Inscription ───────────────────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|unique:users,phone',
            'country'    => 'required|string',
            'city'       => 'required|string',
            'password'   => 'required|string|min:6',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'email'      => $request->email,
            'country'    => $request->country,
            'city'       => $request->city,
            'password'   => Hash::make($request->password),
        ]);

        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    // ── Connexion — accepte téléphone OU email ────────────────────
    public function login(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = null;
        if ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Téléphone ou email requis.',
            ], 422);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
                'errors'  => ['phone' => ['Identifiants incorrects.']],
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé. Contactez le support.',
            ], 403);
        }

        $token = $user->createToken('user-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // ── Déconnexion ───────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Déconnecté avec succès.',
        ]);
    }

    // ── Profil connecté ───────────────────────────────────────────
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    }

    // ── Mot de passe oublié ───────────────────────────────────────
    // Accepte { phone: "..." } OU { email: "..." }
    public function forgotPassword(Request $request)
    {
        if (!$request->filled('email') && !$request->filled('phone')) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez fournir votre email ou numéro de téléphone.',
            ], 422);
        }

        // Trouver l'utilisateur
        $user = null;
        if ($request->filled('email')) {
            $request->validate(['email' => 'email']);
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::where('phone', $request->phone)->first();
        }

        // Réponse neutre — ne révèle pas si le compte existe
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'Si ce compte existe, vous recevrez les instructions.',
            ]);
        }

        // Générer un code à 6 chiffres
        $code       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $identifier = $user->email ?? $user->phone;

        // Sauvegarder dans password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $identifier],
            [
                'email'      => $identifier,
                'token'      => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // ── Envoi email ──────────────────────────────────────────
        if ($user->email) {
            try {
                Mail::raw(
                    "Bonjour {$user->first_name},\n\n" .
                    "Votre code TopTopGo : $code\n\n" .
                    "Ce code expire dans 15 minutes.\n" .
                    "Si vous n'avez pas fait cette demande, ignorez ce message.",
                    fn($m) => $m->to($user->email)
                                ->subject('🔐 Code de réinitialisation TopTopGo')
                );
            } catch (\Exception $e) {
                Log::error('Mail reset error: ' . $e->getMessage());
            }
        }

        // ── Envoi SMS (à connecter selon opérateur) ──────────────
        if ($user->phone) {
            // TODO: Africa's Talking / Twilio / opérateur local
            Log::info("RESET CODE [{$user->phone}]: $code");
        }

        return response()->json([
            'success' => true,
            'message' => 'Code de réinitialisation envoyé.',
        ]);
    }

    // ── Réinitialisation du mot de passe ─────────────────────────
    // Corps : { phone/email, token, password, password_confirmation }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!$request->filled('email') && !$request->filled('phone')) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou téléphone requis.',
            ], 422);
        }

        $identifier = $request->email ?? $request->phone;

        $record = DB::table('password_reset_tokens')
            ->where('email', $identifier)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect ou introuvable.',
            ], 422);
        }

        // Vérifier expiration (15 min)
        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $identifier)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Veuillez en demander un nouveau.',
            ], 422);
        }

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $identifier)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }
}