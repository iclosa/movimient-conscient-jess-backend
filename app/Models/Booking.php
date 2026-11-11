<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class Booking extends Model
{
    public const STATUS_BOOKED = 'booked';

    public const STATUS_WAITLIST = 'waitlist';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_NO_SHOW = 'no_show';

    /** Estados que ocupan plaza en la clase. */
    public const OCCUPYING_STATUSES = [self::STATUS_BOOKED, self::STATUS_ATTENDED, self::STATUS_NO_SHOW];

    public const PAYMENT_PASS = 'pass';

    public const PAYMENT_FREE = 'free';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_PENDING = 'pending';

    public const STATUS_LABELS = [
        self::STATUS_BOOKED => 'Reservada',
        self::STATUS_WAITLIST => 'Llista d\'espera',
        self::STATUS_CANCELLED => 'Cancel·lada',
        self::STATUS_ATTENDED => 'Ha vingut',
        self::STATUS_NO_SHOW => 'No s\'ha presentat',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
            'rated_at' => 'datetime',
            'late_cancellation' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function userPass(): BelongsTo
    {
        return $this->belongsTo(UserPass::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('status', self::OCCUPYING_STATUSES);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [...self::OCCUPYING_STATUSES, self::STATUS_WAITLIST]);
    }

    public function getAttendeeNameAttribute(): string
    {
        return $this->child?->name ?? $this->user?->name ?? '—';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
