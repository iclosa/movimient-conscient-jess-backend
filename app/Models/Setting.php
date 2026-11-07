<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Unguarded]
class Setting extends Model
{
    /**
     * Valores por defecto de los ajustes del centro. Se editan desde el panel.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'center_name' => 'Moviment Conscient by Jess',
        'center_address' => 'Vallirana (Barcelona)',
        'center_phone' => '',
        'center_email' => '',
        'center_instagram' => '',
        'center_website' => '',
        'center_latitude' => '41.3833',
        'center_longitude' => '1.9167',
        'center_timezone' => 'Europe/Madrid',
        'cancellation_hours' => '12',
        'booking_open_days' => '14',
        'waitlist_enabled' => '1',
        'default_capacity' => '12',
        'welcome_message' => 'Benvingut/da a Moviment Conscient',
        'terms_url' => '',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings.all', fn () => self::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
