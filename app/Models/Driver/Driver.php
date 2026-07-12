<?php

namespace App\Models\Driver;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\DriverDocument;
use App\Models\Wallet;
use App\Models\Trip;
use App\Models\DriverLocation;
use App\Models\SupportMessage;
use App\Models\Company;
use App\Models\VehicleDriverShift;

class Driver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'birth_date', 'birth_place', 'country_birth',
        'phone', 'profile_photo', 'id_card_front', 'id_card_back',
        'license_front', 'license_back', 'vehicle_registration', 'insurance',
        'id_card_issue_date', 'id_card_expiry_date', 'id_card_issue_city', 'id_card_issue_country',
        'license_issue_date', 'license_expiry_date', 'license_issue_city', 'license_issue_country',
        'vehicle_plate', 'vehicle_brand', 'vehicle_model', 'vehicle_type', 'vehicle_color',
        'vehicle_country', 'vehicle_city', 'vehicle_lat', 'vehicle_lng', 'status', 'driver_status',
        'password', 'company_id', 'otp', 'otp_expires_at',
    ];

    protected $hidden = ['password'];

    // ================================================================
    // ACCESSORS — Retourne toujours une URL complète pour les fichiers
    // Gère 2 cas :
    //   1. URL complète (http/https) → retournée telle quelle
    //   2. Chemin relatif            → URL locale via Railway Volume
    //   3. Null / vide               → retourne null
    // ================================================================

    private function resolveFileUrl(?string $value): ?string
    {
        if (empty($value)) return null;

        // Déjà une URL complète
        if (str_starts_with($value, 'http')) return $value;

        // Chemin relatif → URL locale (Railway Volume via storage:link)
        return url('storage/' . ltrim($value, '/'));
    }

    public function getProfilePhotoAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getIdCardFrontAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getIdCardBackAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getLicenseFrontAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getLicenseBackAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getVehicleRegistrationAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    public function getInsuranceAttribute($value): ?string
    {
        return $this->resolveFileUrl($value);
    }

    // ================================================================
    // RELATIONS
    // ================================================================

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function documents()
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function locations()
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(DriverLocation::class)->latestOfMany();
    }

    public function supportMessages()
    {
        return $this->morphMany(SupportMessage::class, 'recipient');
    }

    public function withdrawals()
    {
        return $this->hasMany(\App\Models\Withdrawal::class);
    }

    // ── Récap gains / commission / solde (page admin "Retraits chauffeurs") ─
    // Même logique que Company::totalGrossRevenue() : basée sur les courses
    // terminées, pas sur les transactions wallet (qui ne stockent que le net).
    public function totalGrossRevenue(): float
    {
        return (float) Trip::where('driver_id', $this->id)->where('status', 'completed')->sum('amount');
    }

    // Commission TopTopGo — 10% flat, cf. App\Listeners\CreditDriverWallet.
    public function totalCommissionTaken(): float
    {
        return round($this->totalGrossRevenue() * 0.10, 2);
    }

    public function totalNetRevenue(): float
    {
        return round($this->totalGrossRevenue() - $this->totalCommissionTaken(), 2);
    }

    public function withdrawalsPaid(): float
    {
        return (float) $this->withdrawals()->where('status', 'success')->sum('amount');
    }

    // Solde réel disponible = solde du wallet (déjà net de commission ET déjà
    // décrémenté des retraits en attente/payés, cf. WalletService::debit()
    // appelé dès la DEMANDE de retrait, pas seulement à l'approbation).
    public function availableBalance(): float
    {
        return (float) ($this->wallet?->balance ?? 0);
    }

    /**
     * Récapitulatif complet pour les pages de retrait (admin "Retraits
     * chauffeurs") : CA brut, commission TopTopGo déjà prélevée, déjà payé,
     * et solde réel disponible côté wallet chauffeur.
     */
    public function withdrawalRecap(): array
    {
        return [
            'gross_revenue'    => $this->totalGrossRevenue(),
            'commission_taken' => $this->totalCommissionTaken(),
            'net_revenue'      => $this->totalNetRevenue(),
            'withdrawals_paid' => $this->withdrawalsPaid(),
            'available_balance' => $this->availableBalance(),
        ];
    }

    // ✅ Relation inverse manquante : provoquait un crash ("Server Error")
    // sur toute requête eager-loadant driver.company (ex: GET /user/trips)
    // car Eloquent appelle cette méthode pour résoudre la relation.
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // ── Flotte société : véhicules & créneaux ──────────────────────
    public function vehicleShifts()
    {
        return $this->hasMany(VehicleDriverShift::class);
    }

    public function activeVehicleShifts()
    {
        return $this->hasMany(VehicleDriverShift::class)->where('status', 'active');
    }

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_driver_shifts')
                    ->wherePivot('status', 'active')
                    ->withPivot(['id', 'day_of_week', 'specific_date', 'start_time', 'end_time', 'is_primary', 'status'])
                    ->distinct();
    }
}
