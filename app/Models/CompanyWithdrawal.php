<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyWithdrawal extends Model
{
    protected $fillable = [
        'company_id', 'amount', 'method', 'status',
        'transaction_ref', 'notes', 'processed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
