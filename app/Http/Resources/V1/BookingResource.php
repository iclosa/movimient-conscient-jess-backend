<?php

namespace App\Http\Resources\V1;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->classSession;
        $cancellable = in_array($this->status, [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST], true)
            && $session && ! $session->isPast();
        $deadline = $session?->starts_at->copy()->subHours((int) Setting::get('cancellation_hours', '12'));

        $practised = $this->status === Booking::STATUS_ATTENDED
            || ($this->status === Booking::STATUS_BOOKED && $session?->isPast());

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            ...Payment::apiFields($this->payment),
            'attendee' => [
                'type' => $this->child_id ? 'child' : 'user',
                'child_id' => $this->child_id,
                'name' => $this->attendee_name,
            ],
            'can_cancel' => $cancellable,
            'free_cancellation_until' => $cancellable && $this->status === Booking::STATUS_BOOKED ? $deadline?->toIso8601String() : null,
            'is_free_cancellation' => $cancellable && $deadline?->isFuture(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'late_cancellation' => $this->late_cancellation,
            'can_rate' => $practised && $this->rating === null,
            'rating' => $this->rating,
            'rating_comment' => $this->rating_comment,
            'session' => new ClassSessionResource($this->whenLoaded('classSession')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
