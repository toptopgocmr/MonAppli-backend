<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    protected $fillable = [
        'name', 'added_by_type', 'added_by_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Liste des noms actifs, triée alphabétiquement — utilisée pour peupler
    // tous les menus déroulants "type de véhicule" de l'application.
    public static function activeNames(): array
    {
        return static::where('is_active', true)->orderBy('name')->pluck('name')->all();
    }

    // Ajoute un nouveau type s'il n'existe pas déjà (insensible à la casse/espaces).
    // Utilisé quand une société, un chauffeur ou un admin saisit un type inédit.
    public static function addIfMissing(?string $name, ?string $byType = null, $byId = null): ?self
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true]);
            }
            return $existing;
        }

        return static::create([
            'name'          => $name,
            'added_by_type' => $byType,
            'added_by_id'   => $byId,
            'is_active'     => true,
        ]);
    }
}
