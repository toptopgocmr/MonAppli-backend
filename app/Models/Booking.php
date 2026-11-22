<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'seats',
        'passengers',
        'amount',
        'service_fee',
        'status',
        'booked_at',
        'cancellation_reason',
        'cancelled_at',
        'boarded_at',
    ];

    protected $casts = [
        'booked_at'    => 'datetime',
        'cancelled_at' => 'datetime',
        'boarded_at'   => 'datetime',
        'amount'       => 'float',
        'service_fee'  => 'float',
    ];

    // `amount` = prix de la course (places + bagages) ; le client paie en
    // plus des frais de service TopTopGo → `total_to_pay`.
    protected $appends = ['total_to_pay'];

    /** Frais de service : figés à la réservation, sinon calculés avec le réglage actuel. */
    public function serviceFeeAmount(): float
    {
        return $this->service_fee !== null
            ? (float) $this->service_fee
            : \App\Services\Payment\ServiceFee::for((float) $this->amount);
    }

    public function getTotalToPayAttribute(): float
    {
        return round((float) $this->amount + $this->serviceFeeAmount(), 2);
    }

    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAID      = 'paid';
    const STATUS_COMPLETED = 'completed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * ✅ Source de vérité unique pour "cette réservation a été payée".
     *
     * Le statut `status` seul ne suffit pas : une fois payée, le chauffeur
     * peut confirmer la réservation (`status` passe alors à 'confirmed',
     * pas 'paid'), donc on ne peut pas se fier uniquement à
     * `status === 'paid'` après ce point. On vérifie donc AUSSI la relation
     * `payment` (mise à jour par le webhook Peex/Flutterwave ou le polling
     * client — voir WebhookController::applyPeexStatus() et
     * UserPaymentController::status()).
     */
    public function isPaid(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_COMPLETED) {
            return true;
        }

        return $this->payment()->where('status', 'success')->exists();
    }

    /**
     * Scope : ne garder que les réservations réellement payées.
     * Utilisé partout où une réservation ne doit être visible/actionnable
     * qu'après paiement confirmé côté client (app chauffeur, dashboards
     * société et admin).
     */
    public function scopePaid($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', [self::STATUS_PAID, self::STATUS_COMPLETED])
              ->orWhereHas('payment', fn ($p) => $p->where('status', 'success'));
        });
    }
}
