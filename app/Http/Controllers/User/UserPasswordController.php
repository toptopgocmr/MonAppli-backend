<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserPasswordController extends Controller
{
    /**
     * PUT /api/user/password
     * Mise à jour du mot de passe du client connecté
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.',
                'errors'  => ['current_password' => ['Mot de passe actuel incorrect.']],
            ], 422);
        }

        // Empêcher de remettre le même mot de passe
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }
}