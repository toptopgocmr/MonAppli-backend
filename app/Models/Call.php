<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Call — Appels voix/vidéo entre client et chauffeur
 *
 * Namespaces corrects selon votre projet :
 *   - Client  : App\Models\User\User
 *   - Chauffeur: App\Models\Driver\Driver
 */
class Call extends Model
{
    protected $fillable = [
        'trip_id',
        'caller_type',
        'caller_id',
        'receiver_type',
        'receiver_id',
        'type',            // 'audio' | 'video'
        'status',          // 'initiated' | 'answered' | 'missed' | 'ended'
        'duration_seconds',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function caller()
    {
        return $this->morphTo();
    }

    public function receiver()
    {
        return $this->morphTo();
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeForTrip($query, int $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    /** Appels en cours (pas encore terminés) */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initiated', 'answered']);
    }

    /**
     * Appel actif entre deux parties précises (peu importe qui a appelé
     * qui), sans dépendre d'un trip_id — utilisé pour les appels
     * support/société qui ne sont pas toujours liés à un trajet.
     */
    public function scopeBetweenParties($query, string $typeA, int $idA, string $typeB, int $idB)
    {
        return $query->where(function ($q) use ($typeA, $idA, $typeB, $idB) {
            $q->where(function ($q2) use ($typeA, $idA, $typeB, $idB) {
                $q2->where('caller_type', $typeA)->where('caller_id', $idA)
                   ->where('receiver_type', $typeB)->where('receiver_id', $idB);
            })->orWhere(function ($q2) use ($typeA, $idA, $typeB, $idB) {
                $q2->where('caller_type', $typeB)->where('caller_id', $idB)
                   ->where('receiver_type', $typeA)->where('receiver_id', $idA);
            });
        });
    }

    /**
     * Un appel marqué "actif" en base mais en réalité abandonné : personne
     * n'a décroché après 90s de sonnerie, ou un appel "answered" qui traîne
     * depuis des heures (onglet fermé/app tuée sans raccrocher proprement).
     * Sans ça, un seul appel mal terminé bloque indéfiniment tout nouvel
     * appel entre les deux mêmes parties (ex: société ↔ support).
     */
    public function isStale(): bool
    {
        if ($this->status === 'initiated') {
            return $this->created_at && $this->created_at->diffInSeconds(now()) > 90;
        }
        if ($this->status === 'answered') {
            $ref = $this->started_at ?? $this->created_at;
            return $ref && $ref->diffInHours(now()) > 6;
        }
        return false;
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return '0:00';
        // ✅ abs() : protège aussi contre d'anciens enregistrements dont la
        // durée aurait été calculée avant le correctif du diff Carbon
        // (affichait par ex. "-1:-22" au lieu de "1:22").
        $seconds = abs($this->duration_seconds);
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return sprintf('%d:%02d', $m, $s);
    }
}
