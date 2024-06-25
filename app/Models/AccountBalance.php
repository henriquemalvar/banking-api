<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    protected $fillable = [
        'account_id',
        'currency',
        'balance',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
