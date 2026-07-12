<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRecording extends Model
{
    protected $fillable = [
        'call_id',
        'recorded_by_type',
        'recorded_by_id',
        'path',
        'size_bytes',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function recordedBy()
    {
        return $this->morphTo(__FUNCTION__, 'recorded_by_type', 'recorded_by_id');
    }
}
