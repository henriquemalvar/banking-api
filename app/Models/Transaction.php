<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'account_id',
        'amount',
        'original_amount',
        'currency',
        'type',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
