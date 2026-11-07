<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class PracticeStreak
{
    /**
     * Racha de práctica en semanas consecutivas con al menos una clase hecha.
     * La semana en curso no rompe la racha si todavía no ha practicado.
     *
     * @return array{weeks: int, classes_this_week: int, total_classes: int}
     */
    public function for(User $user): array
    {
        $practisedStarts = Booking::query()
            ->where('bookings.user_id', $user->id)
            ->whereIn('bookings.status', [Booking::STATUS_BOOKED, Booking::STATUS_ATTENDED])
            ->join('class_sessions', 'class_sessions.id', '=', 'bookings.class_session_id')
            ->where('class_sessions.status', 'scheduled')
            ->where('class_sessions.starts_at', '<=', now())
            ->pluck('class_sessions.starts_at');

        $weeks = $practisedStarts
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek()->toDateString())
            ->unique()
            ->flip();

        $cursor = now()->startOfWeek();
        $thisWeek = $weeks->has($cursor->toDateString());

        if (! $thisWeek) {
            $cursor = $cursor->subWeek();
        }

        $streak = 0;
        while ($weeks->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subWeek();
        }

        return [
            'weeks' => $streak,
            'classes_this_week' => $practisedStarts
                ->filter(fn ($date) => Carbon::parse($date)->gte(now()->startOfWeek()))
                ->count(),
            'total_classes' => $practisedStarts->count(),
        ];
    }
}
