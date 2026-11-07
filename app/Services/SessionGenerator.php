<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\ScheduleTemplate;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class SessionGenerator
{
    /**
     * Crea las clases concretas del rango a partir del horario semanal.
     * No duplica clases que ya existen (misma plantilla y misma hora).
     *
     * @return int Número de clases creadas.
     */
    public function generate(CarbonInterface $from, CarbonInterface $to, ?ScheduleTemplate $only = null): int
    {
        $templates = $only
            ? collect([$only])
            : ScheduleTemplate::query()->active()->with(['activity', 'room'])->get();

        $created = 0;

        foreach ($templates as $template) {
            foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
                if ($day->dayOfWeekIso !== $template->weekday) {
                    continue;
                }
                if ($template->valid_from && $day->lt($template->valid_from)) {
                    continue;
                }
                if ($template->valid_until && $day->gt($template->valid_until)) {
                    continue;
                }

                [$hour, $minute] = array_map('intval', explode(':', $template->start_time));
                $startsAt = $day->copy()->setTime($hour, $minute);

                $session = ClassSession::query()->firstOrCreate(
                    ['schedule_template_id' => $template->id, 'starts_at' => $startsAt],
                    [
                        'activity_id' => $template->activity_id,
                        'room_id' => $template->room_id,
                        'instructor_id' => $template->instructor_id,
                        'ends_at' => $startsAt->copy()->addMinutes($template->duration_minutes),
                        'capacity' => $template->capacity
                            ?? $template->room?->capacity
                            ?? (int) Setting::get('default_capacity', '12'),
                        'price_cents' => $template->price_cents > 0 ? $template->price_cents : (int) $template->activity?->price_cents,
                        'status' => ClassSession::STATUS_SCHEDULED,
                    ],
                );

                if ($session->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }
}
