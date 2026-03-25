<?php

namespace App\Models\Admin;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;

class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'role_id',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    public function role()
    {
        return $this->belongsTo(Role::class);
    }



    public function hasPermission($permissionName)
    {
        return $this->role
        && $this->role->permissions
        ->pluck('name')
        ->contains($permissionName);
    }



    public function isSuperAdmin()
    {
        return $this->role->name === "Super Admin";
    }


}