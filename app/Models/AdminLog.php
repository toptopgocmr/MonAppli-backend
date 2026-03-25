<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\AdminUser;

class AdminLog extends Model
{
    protected $fillable = [
        'admin_id', 'action', 'model', 'model_id',
        'old_data', 'new_data', 'ip_address',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function admin() { return $this->belongsTo(AdminUser::class); }
}
