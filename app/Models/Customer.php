<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'address',
        'notes',
        'credit_limit',
        'balance_due',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }
}
