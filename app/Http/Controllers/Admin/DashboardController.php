<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $weekSessions = ClassSession::query()
            ->scheduled()
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->withCount('occupyingBookings')
            ->get();

        $capacity = $weekSessions->sum('capacity');
        $taken = $weekSessions->sum('occupying_bookings_count');

        $todaySessions = $weekSessions->filter(fn (ClassSession $s) => $s->starts_at->isToday());

        return view('admin.dashboard', [
            'stats' => [
                'classes_today' => $todaySessions->count(),
                'bookings_today' => $todaySessions->sum('occupying_bookings_count'),
                'occupancy' => $capacity > 0 ? (int) round($taken / $capacity * 100) : 0,
                'new_students' => User::query()->students()->where('created_at', '>=', now()->subDays(30))->count(),
                'revenue_month' => (int) Payment::query()->where('status', 'paid')
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount_cents'),
                'rating' => Booking::query()->whereNotNull('rating')->where('rated_at', '>=', now()->subDays(60))->avg('rating'),
            ],
            'upcoming' => ClassSession::query()
                ->scheduled()
                ->where('starts_at', '>=', now())
                ->with(['activity', 'instructor'])
                ->withCount(['occupyingBookings', 'waitlistBookings'])
                ->orderBy('starts_at')
                ->limit(7)
                ->get(),
            'latestBookings' => Booking::query()
                ->with(['user', 'child', 'classSession.activity'])
                ->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST, Booking::STATUS_CANCELLED])
                ->latest('updated_at')
                ->limit(6)
                ->get(),
            'expiringPasses' => UserPass::query()
                ->with('user')
                ->usable()
                ->where(function (Builder $q) {
                    $q->whereBetween('expires_on', [today(), today()->addDays(7)])
                        ->orWhere(fn (Builder $r) => $r->whereNotNull('classes_remaining')->where('classes_remaining', '<=', 1));
                })
                ->orderBy('expires_on')
                ->limit(6)
                ->get(),
            'waitlistCount' => Booking::query()->where('status', Booking::STATUS_WAITLIST)
                ->whereHas('classSession', fn (Builder $q) => $q->where('starts_at', '>=', now()))->count(),
            'pendingPayments' => Payment::query()->where('status', 'pending')->count(),
        ]);
    }
}
