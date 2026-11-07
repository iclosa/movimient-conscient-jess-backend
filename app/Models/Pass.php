<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
class Pass extends Model
{
    public const TYPE_PACK = 'pack';

    public const TYPE_SUBSCRIPTION = 'subscription';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function userPasses(): HasMany
    {
        return $this->hasMany(UserPass::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getPriceLabelAttribute(): string
    {
        return Money::format($this->price_cents);
    }
}
