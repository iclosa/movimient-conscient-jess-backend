<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ClassSession;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Les meves reserves. `scope`: upcoming (per defecte) | past | all.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $scope = $request->validate(['scope' => ['nullable', 'in:upcoming,past,all']])['scope'] ?? 'upcoming';

        $query = Booking::query()
            ->where('bookings.user_id', $request->user()->id)
            ->with(['classSession.activity', 'classSession.instructor', 'classSession.room', 'child', 'payment'])
            ->join('class_sessions', 'class_sessions.id', '=', 'bookings.class_session_id')
            ->select('bookings.*');

        match ($scope) {
            'upcoming' => $query->where('class_sessions.starts_at', '>=', now())
                ->whereIn('bookings.status', [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST])
                ->orderBy('class_sessions.starts_at'),
            'past' => $query->where('class_sessions.starts_at', '<', now())->orderByDesc('class_sessions.starts_at'),
            default => $query->orderByDesc('class_sessions.starts_at'),
        };

        return BookingResource::collection($query->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
        ]);

        $child = isset($data['child_id']) ? Child::findOrFail($data['child_id']) : null;

        $booking = $this->bookings->book($request->user(), ClassSession::findOrFail($data['class_session_id']), $child);

        return (new BookingResource($booking->load(['classSession.activity', 'classSession.instructor', 'classSession.room', 'child', 'payment'])))
            ->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $id): BookingResource
    {
        $booking = Booking::query()->where('user_id', $request->user()->id)->findOrFail($id);

        $booking = $this->bookings->cancel($booking);

        return new BookingResource($booking->load(['classSession.activity', 'child', 'payment']));
    }

    public function rate(Request $request, int $id): BookingResource
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::query()->where('user_id', $request->user()->id)->with('classSession')->findOrFail($id);

        $this->bookings->rate($booking, $data['rating'], $data['comment'] ?? null);

        return new BookingResource($booking->refresh()->load(['classSession.activity', 'child', 'payment']));
    }
}
