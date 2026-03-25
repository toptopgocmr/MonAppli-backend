<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use App\Models\Admin\AdminUser;
use App\Models\Driver\Driver;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission($permissionName)
    {
        return $this->permissions->pluck('name')->contains($permissionName);
    }

    public function admins()
    {
        return $this->hasMany(AdminUser::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
}
