<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Driver\Driver;

class Company extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'logo',
        'address', 'city', 'country', 'type', 'status',
        'contact_name', 'description', 'commission_rate',
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
