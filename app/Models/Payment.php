<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = ['transaction_id', 'amount_cents', 'status', 'user_email'];

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }
}
