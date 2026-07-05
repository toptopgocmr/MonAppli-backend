<?php

namespace App\Support;

use App\Models\CompanyAgent;

// Petit utilitaire pour savoir, dans les vues et contrôleurs du panel société,
// si l'utilisateur courant est le compte principal de la société ou un agent
// (comptable, RH, DG...) — et dans ce second cas, quelles sont ses permissions.
class CompanyContext
{
    public static function agent(): ?CompanyAgent
    {
        return app()->bound('current.company_agent') ? app('current.company_agent') : null;
    }

    public static function isAgent(): bool
    {
        return self::agent() !== null;
    }

    // true si l'utilisateur courant (société propriétaire OU agent autorisé)
    // peut accéder à la section donnée. Le compte principal a toujours accès
    // à tout ; un agent doit avoir la permission correspondante (sauf DG,
    // qui a un accès complet).
    public static function can(string $permissionKey): bool
    {
        $agent = self::agent();
        if (!$agent) {
            return true;
        }

        return $agent->can($permissionKey);
    }
}
