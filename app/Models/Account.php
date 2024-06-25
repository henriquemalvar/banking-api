<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'account_number',
    ];

    public function balances()
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

