<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQueryLog extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'question',
        'answer',
        'context',
        'provider',
        'model',
        'status',
        'asked_at',
    ];

    protected $casts = [
        'context' => 'array',
        'asked_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
