<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Driver\Driver;
use App\Models\Wallet;

class DriverAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'birth_date'   => 'required|date',
            'birth_place'  => 'required|string',
            'country_birth'=> 'required|string',
            'phone'        => 'required|string|unique:drivers,phone',
            'password'     => 'required|string|min:6',
        ]);

        $driver = Driver::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'birth_date'    => $request->birth_date,
            'birth_place'   => $request->birth_place,
            'country_birth' => $request->country_birth,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'status'        => 'pending',
        ]);

        Wallet::create(['driver_id' => $driver->id, 'balance' => 0, 'currency' => 'XAF']);

        $token = $driver->createToken('driver-token')->plainTextToken;

        return response()->json(['token' => $token, 'driver' => $driver], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Connexion par téléphone ou email
        $driver = null;
        if ($request->filled('phone')) {
            $driver = Driver::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $driver = Driver::where('email', $request->email)->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Téléphone ou email requis.',
            ], 422);
        }

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Identifiants incorrects.'],
            ]);
        }

        // Compte suspendu — connexion bloquée
        if ($driver->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été suspendu. Veuillez contacter le support TopTopGo.',
            ], 403);
        }

        $token = $driver->createToken('driver-token')->plainTextToken;

        return response()->json(['success' => true, 'token' => $token, 'driver' => $driver]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('wallet'));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required_without:email|string|nullable',
            'email' => 'required_without:phone|email|nullable',
        ]);

        $driver = null;
        if ($request->filled('phone')) {
            $driver = Driver::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $driver = Driver::where('email', $request->email)->first();
        }

        // On retourne toujours succès pour ne pas exposer si le compte existe
        if (!$driver) {
            return response()->json([
                'success' => true,
                'message' => 'Si ce compte existe, un SMS/email de réinitialisation a été envoyé.',
            ]);
        }

        // Générer un code temporaire à 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $driver->update([
            'otp'            => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        // TODO: Envoyer le code par SMS (ex: Twilio, Africa's Talking)
        // SmsService::send($driver->phone, "Votre code TopTopGo : $code");

        return response()->json([
            'success' => true,
            'message' => 'Si ce compte existe, un SMS/email de réinitialisation a été envoyé.',
            // En dev seulement — retirer en production :
            '_dev_code' => config('app.debug') ? $code : null,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'    => 'required_without:email|nullable|string',
            'email'    => 'required_without:phone|nullable|email',
            'code'     => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $driver = null;
        if ($request->filled('phone')) {
            $driver = Driver::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $driver = Driver::where('email', $request->email)->first();
        }

        if (!$driver || !$driver->otp || !Hash::check($request->code, $driver->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide.',
            ], 422);
        }

        if ($driver->otp_expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Code expiré. Veuillez recommencer.',
            ], 422);
        }

        $driver->update([
            'password'       => Hash::make($request->password),
            'otp'            => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }
}