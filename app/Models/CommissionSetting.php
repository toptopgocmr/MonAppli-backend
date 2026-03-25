<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = ['rate', 'description', 'created_by'];

    public static function currentRate(): float
    {
        return (float) (self::latest()->value('rate') ?? 10.00);
    }
}