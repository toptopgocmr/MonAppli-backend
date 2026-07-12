<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\AdminUser;
use App\Models\Driver\Driver;

class SupportMessage extends Model
{
    protected $fillable = [
        'sender_type', 'sender_id',
        'recipient_type', 'recipient_id',
        'content', 'is_read', 'read_at', 'trip_id',
        'refused', 'refused_reason',
    ];

    protected $casts = [
        'refused' => 'boolean',
    ];

    /**
     * Polymorphic sender (AdminUser ou Driver)
     */
    public function sender()
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic recipient (AdminUser ou Driver)
     */
    public function recipient()
    {
        return $this->morphTo();
    }

    /**
     * Si l'expéditeur est un Admin, on récupère l'AdminUser
     */
    public function adminSender()
    {
        return $this->belongsTo(AdminUser::class, 'sender_id')
                    ->where('sender_type', AdminUser::class);
    }

    /**
     * Alias de adminSender() — requis par admin-user.blade.php
     * ($message->admin->name) et AdminUserSupportController (->with('admin')).
     * Sans cette relation, ->with('admin') levait un BadMethodCallException
     * ("Call to undefined relationship [admin]") et faisait planter la page
     * de conversation admin ↔ client à chaque ouverture.
     */
    public function admin()
    {
        return $this->adminSender();
    }

    /**
     * Si le destinataire est un Admin, on récupère l'AdminUser
     */
    public function adminRecipient()
    {
        return $this->belongsTo(AdminUser::class, 'recipient_id')
                    ->where('recipient_type', AdminUser::class);
    }

    /**
     * Si l'expéditeur est un Driver, on récupère le Driver
     */
    public function driverSender()
    {
        return $this->belongsTo(Driver::class, 'sender_id')
                    ->where('sender_type', Driver::class);
    }

    /**
     * Si le destinataire est un Driver, on récupère le Driver
     */
    public function driverRecipient()
    {
        return $this->belongsTo(Driver::class, 'recipient_id')
                    ->where('recipient_type', Driver::class);
    }
}