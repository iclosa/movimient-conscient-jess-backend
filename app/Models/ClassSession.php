<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
class ClassSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Reservas que ocupan plaza (confirmadas, asistidas o no presentadas).
     */
    public function occupyingBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->whereIn('status', Booking::OCCUPYING_STATUSES);
    }

    public function waitlistBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', Booking::STATUS_WAITLIST)->orderBy('created_at');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: ($this->activity?->name ?? 'Classe');
    }

    /**
     * Plazas ocupadas. Usa `occupying_bookings_count` si viene cargado con withCount.
     */
    public function getSpotsTakenAttribute(): int
    {
        return $this->occupying_bookings_count ?? $this->occupyingBookings()->count();
    }

    public function getSpotsLeftAttribute(): int
    {
        return max(0, $this->capacity - $this->spots_taken);
    }

    public function getOccupancyLabelAttribute(): string
    {
        return $this->spots_taken.' / '.$this->capacity;
    }

    public function isFull(): bool
    {
        return $this->spots_left <= 0;
    }

    public function getPriceLabelAttribute(): string
    {
        return Money::format($this->price_cents);
    }
}
