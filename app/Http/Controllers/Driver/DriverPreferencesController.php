<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverPreferencesController extends Controller
{
    // ── Lire les préférences ──────────────────────────────────────────────
    public function show()
    {
        $driver = Driver::find(Auth::id());
        return response()->json([
            'success' => true,
            'data'    => $this->fmt($driver),
        ]);
    }

    // ── Mettre à jour les préférences ─────────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'pref_music'        => 'nullable|boolean',
            'pref_talk'         => 'nullable|boolean',
            'pref_smoking'      => 'nullable|boolean',
            'pref_pets'         => 'nullable|boolean',
            'pref_max_two_back' => 'nullable|boolean',
        ]);

        $driver = Driver::find(Auth::id());
        $driver->update($request->only([
            'pref_music', 'pref_talk', 'pref_smoking',
            'pref_pets', 'pref_max_two_back',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour.',
            'data'    => $this->fmt($driver->fresh()),
        ]);
    }

    private function fmt(Driver $d): array
    {
        return [
            'pref_music'        => (bool) ($d->pref_music        ?? true),
            'pref_talk'         => (bool) ($d->pref_talk         ?? true),
            'pref_smoking'      => (bool) ($d->pref_smoking      ?? false),
            'pref_pets'         => (bool) ($d->pref_pets         ?? false),
            'pref_max_two_back' => (bool) ($d->pref_max_two_back ?? false),
        ];
    }
}
