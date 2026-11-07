<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Unguarded]
class Event extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function confirmedRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class)->where('status', 'confirmed');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->price_cents > 0 ? Money::format($this->price_cents) : 'Gratuït';
    }

    /**
     * "8 / 14" para listados. Requiere withCount('confirmedRegistrations').
     */
    public function getInscritsAttribute(): string
    {
        $count = $this->confirmed_registrations_count ?? $this->confirmedRegistrations()->count();

        return $count.($this->capacity ? ' / '.$this->capacity : '');
    }

    public function getSpotsLeftAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        $taken = $this->confirmed_registrations_count ?? $this->confirmedRegistrations()->count();

        return max(0, $this->capacity - $taken);
    }
}
