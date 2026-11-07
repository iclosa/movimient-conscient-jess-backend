<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $anchor = $request->filled('data') ? Carbon::parse($request->query('data')) : now();
        $start = $anchor->copy()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $sessions = ClassSession::query()
            ->with(['activity', 'instructor'])
            ->withCount('occupyingBookings')
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (ClassSession $s) => $s->starts_at->toDateString());

        $days = collect(range(0, 6))->map(fn (int $i) => $start->copy()->addDays($i));

        return view('admin.calendar', [
            'days' => $days,
            'sessions' => $sessions,
            'start' => $start,
            'end' => $end,
            'previous' => $start->copy()->subWeek()->toDateString(),
            'next' => $start->copy()->addWeek()->toDateString(),
            'isCurrentWeek' => $start->isSameWeek(now()),
        ]);
    }
}
