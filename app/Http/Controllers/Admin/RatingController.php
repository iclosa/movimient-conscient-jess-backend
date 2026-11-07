<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RatingController extends Controller
{
    public function index(): View
    {
        $rated = Booking::query()->whereNotNull('rating');

        $perActivity = Activity::query()
            ->select('activities.*')
            ->selectRaw('AVG(bookings.rating) as average')
            ->selectRaw('COUNT(bookings.rating) as votes')
            ->join('class_sessions', 'class_sessions.activity_id', '=', 'activities.id')
            ->join('bookings', function ($join) {
                $join->on('bookings.class_session_id', '=', 'class_sessions.id')->whereNotNull('bookings.rating');
            })
            ->groupBy('activities.id')
            ->orderByDesc('average')
            ->get();

        $distribution = (clone $rated)
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return view('admin.ratings.index', [
            'average' => (clone $rated)->avg('rating'),
            'total' => (clone $rated)->count(),
            'perActivity' => $perActivity,
            'distribution' => $distribution,
            'latest' => Booking::query()
                ->whereNotNull('rating')
                ->with(['user', 'child', 'classSession.activity'])
                ->latest('rated_at')
                ->limit(15)
                ->get(),
        ]);
    }
}
