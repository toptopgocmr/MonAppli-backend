<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CompanyAgent extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'company_id', 'name', 'email', 'phone', 'password',
        'role', 'permissions', 'status', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'permissions'   => 'array',
        'last_login_at' => 'datetime',
    ];

    // Rôles proposés dans le formulaire (libre : d'autres valeurs restent
    // acceptées si besoin, ce champ n'est pas une contrainte DB fermée).
    public const ROLES = [
        'comptable'              => 'Comptable',
        'rh'                     => 'Responsable du personnel (RH)',
        'directeur_general'      => 'Directeur Général',
        'flotte'                 => 'Responsable de la flotte véhicule',
        'marketing_communication'=> 'Marketing & Communication',
        'commercial'             => 'Commercial',
    ];

    // Sections du panel société pouvant être individuellement autorisées.
    public const PERMISSIONS = [
        'drivers'        => 'Chauffeurs',
        'vehicles'       => 'Flotte Véhicules',
        'schedule'       => 'Planning chauffeurs',
        'pricing_grids'  => 'Grilles tarifaires',
        'reservations'   => 'Réservations',
        'itineraries'    => 'Itinéraires',
        'messages'       => 'Messages',
        'withdrawals'    => 'Retraits',
        'revenus'        => 'Revenus',
        'agents'         => 'Gestion des agents',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isDirecteurGeneral(): bool
    {
        return $this->role === 'directeur_general';
    }

    // Le Directeur Général a toujours un accès complet, quelles que soient
    // les cases cochées à la création (décision produit).
    public function can(string $permissionKey): bool
    {
        if ($this->isDirecteurGeneral()) {
            return true;
        }

        return in_array($permissionKey, $this->permissions ?? [], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
