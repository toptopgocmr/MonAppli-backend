<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message — Échanges entre client et chauffeur dans un trajet
 *
 * Structure DB :
 *   trip_id, sender_type, sender_id, receiver_type, receiver_id,
 *   content, is_read, read_at, refused, refused_reason
 */
class Message extends Model
{
    protected $fillable = [
        'trip_id',
        'sender_type',
        'sender_id',
        'receiver_type',
        'receiver_id',
        'content',
        'is_read',
        'read_at',
        'refused',        // ✅ AJOUTÉ — bloqué par la modération
        'refused_reason', // ✅ AJOUTÉ — raison du blocage
    ];

    protected $casts = [
        'is_read'  => 'boolean',
        'refused'  => 'boolean',
        'read_at'  => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function sender()
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

    public function scopeVisible($query)
    {
        return $query->where('refused', false);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}