<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\AdminUser;

class SosAlert extends Model
{
    protected $fillable = [
        'sender_type', 'sender_id', 'trip_id',
        'lat', 'lng', 'message',
        'status', 'treated_by', 'treated_at',
    ];

    protected $casts = [
        'treated_at' => 'datetime',
    ];

    public function sender() { return $this->morphTo(); }
    public function trip() { return $this->belongsTo(Trip::class); }
    public function treatedBy() { return $this->belongsTo(AdminUser::class, 'treated_by'); }
}