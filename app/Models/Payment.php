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
        'pass' => 'Bo',
        'free' => 'Gratuït',
        'other' => 'Altres',
    ];

    public const STATUSES = [
        'paid' => 'Cobrat',
        'pending' => 'Pendent',
        'refunded' => 'Retornat',
        'failed' => 'Fallit',
        'cancelled' => 'Cancel·lat',
    ];

    /** Color del badge de cada estat: és l'únic estat de pagament que es mostra a tot arreu. */
    public const STATUS_TONES = [
        'paid' => 'success',
        'pending' => 'warning',
        'refunded' => 'info',
        'failed' => 'danger',
        'cancelled' => 'muted',
    ];

    protected static function booted(): void
    {
        // La data de cobrament segueix l'estat: en passar a «Cobrat» s'omple sola i en tornar a «Pendent» s'esborra.
        static::saving(function (Payment $payment) {
            if ($payment->status === 'paid' && $payment->paid_at === null) {
                $payment->paid_at = now();
            } elseif ($payment->status === 'pending') {
                $payment->paid_at = null;
            }
        });
    }

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

    /** Hi ha diners cobrats de debò (un cobrament de 0 € per bo o classe gratuïta no compta). */
    public function isCollected(): bool
    {
        return $this->status === 'paid' && $this->amount_cents > 0;
    }

    /**
     * Anul·la el cobrament quan es cancel·la la reserva, tret que ja s'hagin cobrat diners:
     * retornar-los és una decisió manual (estat «Retornat»).
     */
    public function cancelUnlessCollected(): void
    {
        if (in_array($this->status, ['pending', 'paid'], true) && ! $this->isCollected()) {
            $this->update(['status' => 'cancelled']);
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function getStatusToneAttribute(): string
    {
        return self::STATUS_TONES[$this->status] ?? 'muted';
    }

    public function getAmountLabelAttribute(): string
    {
        return Money::format($this->amount_cents);
    }
}
