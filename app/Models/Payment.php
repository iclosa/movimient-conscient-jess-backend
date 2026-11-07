<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
class Payment extends Model
{
    public const METHODS = [
        'cash' => 'Efectiu',
        'bizum' => 'Bizum',
        'transfer' => 'Transferència',
        'card' => 'Targeta (datàfon)',
        'stripe' => 'Stripe (app)',
        'other' => 'Altres',
    ];

    public const STATUSES = [
        'paid' => 'Cobrat',
        'pending' => 'Pendent',
        'refunded' => 'Retornat',
        'failed' => 'Fallit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountLabelAttribute(): string
    {
        return Money::format($this->amount_cents);
    }
}
