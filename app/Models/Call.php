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

    // ── Accessors ──────────────────────────────────────────────────

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return '0:00';
        $m = intdiv($this->duration_seconds, 60);
        $s = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $m, $s);
    }
}