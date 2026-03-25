<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
