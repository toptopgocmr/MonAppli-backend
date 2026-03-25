<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverPasswordController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $driver = $request->user();

        if (!Hash::check($request->current_password, $driver->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $driver->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}