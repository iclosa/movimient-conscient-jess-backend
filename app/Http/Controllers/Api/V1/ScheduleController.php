<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ActivityResource;
use App\Http\Resources\V1\ClassSessionResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    public function activities(): AnonymousResourceCollection
    {
        return ActivityResource::collection(Activity::query()->active()->orderBy('sort_order')->orderBy('name')->get());
    }

    /**
     * Classes per rang de dates (per defecte, els pròxims 7 dies). Màxim 31 dies.
     */
    public function sessions(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'activity_id' => ['nullable', 'integer'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfDay();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : $from->copy()->addDays(6)->endOfDay();
        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31)->endOfDay();
        }

        $userId = $request->user()->id;

        $sessions = ClassSession::query()
            ->with(['activity', 'instructor', 'room'])
            ->with(['bookings' => fn ($q) => $q->where('user_id', $userId)->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST, Booking::STATUS_ATTENDED])])
            ->whereBetween('starts_at', [$from, $to])
            ->when($data['activity_id'] ?? null, fn ($q, $id) => $q->where('activity_id', $id))
            ->orderBy('starts_at')
            ->get();

        return ClassSessionResource::collection($sessions);
    }

    public function show(Request $request, int $id): ClassSessionResource
    {
        $userId = $request->user()->id;

        $session = ClassSession::query()
            ->with(['activity', 'instructor', 'room'])
            ->with(['bookings' => fn ($q) => $q->where('user_id', $userId)->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST, Booking::STATUS_ATTENDED])])
            ->findOrFail($id);

        return new ClassSessionResource($session);
    }
}
