<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'locale', 'birth_date', 'notes', 'is_active', 'marketing_consent'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_STUDENT = 'student';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'marketing_consent' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function userPasses(): HasMany
    {
        return $this->hasMany(UserPass::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function videoAccesses(): HasMany
    {
        return $this->hasMany(VideoAccess::class);
    }

    public function gratitudeEntries(): HasMany
    {
        return $this->hasMany(GratitudeEntry::class);
    }

    /**
     * Resumen para listados. Requiere la relación userPasses cargada.
     */
    public function getPassSummaryAttribute(): string
    {
        $usable = $this->userPasses->filter(fn (UserPass $pass) => $pass->isUsable());

        if ($usable->isEmpty()) {
            return '—';
        }

        if ($usable->contains(fn (UserPass $pass) => $pass->isUnlimited())) {
            return 'Il·limitat';
        }

        return $usable->sum('classes_remaining').' classes';
    }

    /**
     * Bono que se usaría para la próxima reserva: el usable que caduca antes.
     */
    public function bestUsablePass(): ?UserPass
    {
        return $this->userPasses()
            ->usable()
            ->orderByRaw('expires_on IS NULL')
            ->orderBy('expires_on')
            ->first();
    }
}
