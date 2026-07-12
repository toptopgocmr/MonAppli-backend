<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallRecording extends Model
{
    protected $fillable = [
        'call_id',
        'source',        // 'browser' (MediaRecorder, admin/société) | 'cloud' (Agora Cloud Recording, client↔chauffeur)
        'recorded_by_type',
        'recorded_by_id',
        'path',
        'storage_disk',  // disque Laravel où lire `path` : 'public' (browser) | 'agora_recordings' (cloud)
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
