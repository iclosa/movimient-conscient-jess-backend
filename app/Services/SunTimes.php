<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

class SunTimes
{
    /**
     * Hores del sol per a la ubicació del centre (ajustos `center_*`).
     *
     * @return array{location: string, latitude: float, longitude: float, timezone: string, days: array<int, array<string, mixed>>}
     */
    public function forCenter(?string $date = null, int $days = 1): array
    {
        $timezone = Setting::get('center_timezone', 'Europe/Madrid');
        $latitude = (float) Setting::get('center_latitude');
        $longitude = (float) Setting::get('center_longitude');
        $first = CarbonImmutable::parse($date ?? 'today', $timezone)->startOfDay();

        return [
            'location' => (string) Setting::get('center_address'),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $timezone,
            'days' => collect(range(0, max($days, 1) - 1))
                ->map(fn (int $offset) => $this->day($first->addDays($offset), $latitude, $longitude, $timezone))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function day(CarbonImmutable $day, float $latitude, float $longitude, string $timezone): array
    {
        $info = date_sun_info($day->setTime(12, 0)->getTimestamp(), $latitude, $longitude);
        $at = fn (string $key): ?string => is_int($info[$key] ?? null)
            ? CarbonImmutable::createFromTimestamp($info[$key], $timezone)->toIso8601String()
            : null;

        $sunrise = is_int($info['sunrise'] ?? null) ? $info['sunrise'] : null;
        $sunset = is_int($info['sunset'] ?? null) ? $info['sunset'] : null;

        return [
            'date' => $day->toDateString(),
            'first_light' => $at('civil_twilight_begin'),
            'sunrise' => $at('sunrise'),
            'solar_noon' => $at('transit'),
            'sunset' => $at('sunset'),
            'last_light' => $at('civil_twilight_end'),
            'daylight_minutes' => $sunrise && $sunset ? intdiv($sunset - $sunrise, 60) : null,
        ];
    }
}
