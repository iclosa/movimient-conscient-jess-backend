<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class UserPass extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_EXHAUSTED = 'exhausted';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pass(): BelongsTo
    {
        return $this->belongsTo(Pass::class);
    }

    /**
     * Bonos que se pueden usar hoy: activos, vigentes y con crédito (o ilimitados).
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('starts_on', '<=', today())
            ->where(function (Builder $q) {
                $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', today());
            })
            ->where(function (Builder $q) {
                $q->whereNull('classes_remaining')->orWhere('classes_remaining', '>', 0);
            });
    }

    /**
     * Misma regla que el scope usable(), para bonos ya cargados en memoria.
     */
    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->starts_on->lte(today())
            && ($this->expires_on === null || $this->expires_on->gte(today()))
            && ($this->classes_remaining === null || $this->classes_remaining > 0);
    }

    public function getRemainingLabelAttribute(): string
    {
        return $this->isUnlimited() ? 'Il·limitat' : $this->classes_remaining.' / '.$this->classes_total;
    }

    public function isUnlimited(): bool
    {
        return $this->classes_remaining === null;
    }

    /**
     * Descuenta una clase. Marca el bono como agotado al llegar a 0.
     */
    public function consume(): void
    {
        if ($this->isUnlimited()) {
            return;
        }

        $this->decrement('classes_remaining');

        if ($this->fresh()->classes_remaining <= 0) {
            $this->update(['status' => self::STATUS_EXHAUSTED]);
        }
    }

    /**
     * Devuelve una clase al bono (por ejemplo, tras una cancelación a tiempo).
     */
    public function refund(): void
    {
        if ($this->isUnlimited()) {
            return;
        }

        $this->increment('classes_remaining');

        if ($this->status === self::STATUS_EXHAUSTED) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }
}
