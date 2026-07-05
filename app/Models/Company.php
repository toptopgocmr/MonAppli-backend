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

    // ── Retraits mensuels ──────────────────────────────────────────
    // Revenu net = somme des courses terminées des chauffeurs de la société,
    // moins la commission plateforme (commission_rate %).
    public function totalNetRevenue(): float
    {
        $driverIds = Driver::where('company_id', $this->id)->pluck('id');
        $gross = (float) Trip::whereIn('driver_id', $driverIds)->where('status', 'completed')->sum('amount');
        $rate  = (float) ($this->commission_rate ?? 0);

        return round($gross * (1 - $rate / 100), 2);
    }

    // Retraits déjà demandés (en attente ou payés) : à déduire du solde disponible.
    public function withdrawalsCommitted(): float
    {
        return (float) $this->withdrawals()->whereIn('status', ['pending', 'success'])->sum('amount');
    }

    public function availableBalance(): float
    {
        return max(0, round($this->totalNetRevenue() - $this->withdrawalsCommitted(), 2));
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
