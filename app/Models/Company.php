<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Driver\Driver;
use App\Models\Trip;

class Company extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'logo',
        'address', 'city', 'country', 'type', 'status',
        'contact_name', 'description', 'commission_rate',
        'bank_name', 'bank_iban', 'bank_swift', 'bank_address',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'commission_rate' => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────
    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    // Comptes agents secondaires (comptable, RH, flotte...) rattachés à cette
    // société — cf. CompanyAgent. Utilisé notamment par l'admin plateforme
    // pour lister/superviser tous les agents, toutes sociétés confondues.
    public function agents()
    {
        return $this->hasMany(CompanyAgent::class);
    }

    public function activeDrivers()
    {
        return $this->hasMany(Driver::class)->where('status', 'approved');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function pricingGrids()
    {
        return $this->hasMany(PricingGrid::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(CompanyWithdrawal::class);
    }

    // Chat texte Admin (support) ↔ Société — cf. AdminCompanySupportController
    // / CompanySupportController. Manquait totalement avant : seul un appel
    // vocal (CompanyCallController) existait entre société et support.
    public function supportMessages()
    {
        return $this->morphMany(SupportMessage::class, 'recipient');
    }

    // ── Retraits mensuels ──────────────────────────────────────────
    // Chiffre d'affaires brut = somme des courses terminées des chauffeurs
    // de la société, AVANT déduction de la commission plateforme.
    public function totalGrossRevenue(): float
    {
        $driverIds = Driver::where('company_id', $this->id)->pluck('id');
        return (float) Trip::whereIn('driver_id', $driverIds)->where('status', 'completed')->sum('amount');
    }

    // Commission TopTopGo déjà prélevée sur l'ensemble des courses terminées.
    public function totalCommissionTaken(): float
    {
        $rate = (float) ($this->commission_rate ?? 0);
        return round($this->totalGrossRevenue() * $rate / 100, 2);
    }

    // Revenu net = somme des courses terminées des chauffeurs de la société,
    // moins la commission plateforme (commission_rate %).
    public function totalNetRevenue(): float
    {
        return round($this->totalGrossRevenue() - $this->totalCommissionTaken(), 2);
    }

    // Retraits déjà demandés (en attente ou payés) : à déduire du solde disponible.
    public function withdrawalsCommitted(): float
    {
        return (float) $this->withdrawals()->whereIn('status', ['pending', 'success'])->sum('amount');
    }

    // Retraits déjà PAYÉS (status success uniquement) — utile pour le récap
    // "déjà retiré" distinct des demandes encore en attente.
    public function withdrawalsPaid(): float
    {
        return (float) $this->withdrawals()->where('status', 'success')->sum('amount');
    }

    public function availableBalance(): float
    {
        return max(0, round($this->totalNetRevenue() - $this->withdrawalsCommitted(), 2));
    }

    /**
     * Récapitulatif complet pour les pages de retrait (société ET admin) :
     * CA brut, commission TopTopGo déjà prélevée, déjà retiré (payé),
     * en attente de retrait, et solde réel disponible à retirer.
     */
    public function withdrawalRecap(): array
    {
        return [
            'gross_revenue'     => $this->totalGrossRevenue(),
            'commission_taken'  => $this->totalCommissionTaken(),
            'net_revenue'       => $this->totalNetRevenue(),
            'withdrawals_paid'  => $this->withdrawalsPaid(),
            'withdrawals_committed' => $this->withdrawalsCommitted(),
            'available_balance' => $this->availableBalance(),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;
        if (str_starts_with($this->logo, 'http')) return $this->logo;
        return url('storage/' . $this->logo);
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'location'    => 'Location de véhicules',
            'covoiturage' => 'Covoiturage privé',
            default       => 'Location & Covoiturage',
        };
    }

    public function getStatusLibelleAttribute(): string
    {
        return match($this->status) {
            'active'    => 'Active',
            'suspended' => 'Suspendue',
            default     => 'En attente',
        };
    }
}
