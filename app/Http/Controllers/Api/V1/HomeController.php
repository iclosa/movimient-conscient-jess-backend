<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\PromotionResource;
use App\Http\Resources\V1\UserPassResource;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Promotion;
use App\Models\Setting;
use App\Services\PracticeStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Pantalla "Resum": pròxima classe, bons, promocions, properament i reserva anterior per valorar.
     */
    public function __invoke(Request $request, PracticeStreak $streak): JsonResponse
    {
        $user = $request->user();
        $with = ['classSession.activity', 'classSession.instructor', 'classSession.room', 'child'];

        $base = fn () => Booking::query()
            ->where('bookings.user_id', $user->id)
            ->join('class_sessions', 'class_sessions.id', '=', 'bookings.class_session_id')
            ->select('bookings.*')
            ->with($with);

        $next = $base()->where('bookings.status', Booking::STATUS_BOOKED)
            ->where('class_sessions.status', 'scheduled')
            ->where('class_sessions.starts_at', '>=', now())
            ->orderBy('class_sessions.starts_at')
            ->first();

        $toRate = $base()->whereIn('bookings.status', [Booking::STATUS_BOOKED, Booking::STATUS_ATTENDED])
            ->whereNull('bookings.rating')
            ->where('class_sessions.status', 'scheduled')
            ->where('class_sessions.ends_at', '<', now())
            ->orderByDesc('class_sessions.starts_at')
            ->first();

        $passes = $user->userPasses()->usable()->orderBy('expires_on')->get();

        return response()->json([
            'data' => [
                'welcome_message' => Setting::get('welcome_message'),
                'next_booking' => $next ? new BookingResource($next) : null,
                'passes' => UserPassResource::collection($passes),
                'promotions' => PromotionResource::collection(Promotion::query()->current()->orderBy('sort_order')->limit(5)->get()),
                'upcoming_events' => EventResource::collection(Event::query()->published()->upcoming()->orderBy('starts_at')->limit(3)->get()),
                'booking_to_rate' => $toRate ? new BookingResource($toRate) : null,
                'streak' => $streak->for($user),
            ],
        ]);
    }
}
