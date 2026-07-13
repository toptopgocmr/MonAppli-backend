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

    /**
     * La société courante, que l'utilisateur connecté soit le compte
     * principal (guard "company") OU un agent (guard "company_agent").
     *
     * Avant ce correctif, chaque contrôleur du panel société résolvait la
     * société via `auth('company')->user()` uniquement — ce qui renvoie
     * `null` quand c'est un AGENT qui est connecté (DG, comptable...),
     * provoquant une erreur 500 ("Attempt to read property on null") dès
     * qu'un agent ouvrait une section utilisant sa société (ex: Agents,
     * Chauffeurs, Retraits...).
     */
    public static function company(): ?\App\Models\Company
    {
        if ($company = auth('company')->user()) {
            return $company;
        }

        return self::agent()?->company;
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

        return $agent->hasPermission($permissionKey);
    }
}
