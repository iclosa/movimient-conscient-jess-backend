<?php

namespace App\Http\Resources\V1;

use App\Models\Booking;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una classe del calendari. Si la petició és d'una persona identificada i la relació
 * `bookings` s'ha carregat filtrada per ella, inclou `my_bookings` i l'estat personal.
 *
 * Estats: available | full | booked | waitlist | cancelled
 *
 * @mixin ClassSession
 */
class ClassSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mine = $this->relationLoaded('bookings') ? $this->bookings : collect();
        $spotsLeft = $this->spots_left;

        $state = match (true) {
            $this->isCancelled() => 'cancelled',
            $mine->contains(fn (Booking $b) => $b->status === Booking::STATUS_WAITLIST) && ! $mine->contains(fn (Booking $b) => $b->status !== Booking::STATUS_WAITLIST) => 'waitlist',
            $mine->isNotEmpty() => 'booked',
            $spotsLeft <= 0 => 'full',
            default => 'available',
        };

        return [
            'id' => $this->id,
            'title' => $this->display_title,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'is_past' => $this->isPast(),
            'capacity' => $this->capacity,
            'spots_left' => $spotsLeft,
            'state' => $state,
            'price_cents' => $this->price_cents,
            'activity' => new ActivityResource($this->whenLoaded('activity')),
            'instructor' => $this->whenLoaded('instructor', fn () => $this->instructor ? [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'photo_url' => $this->instructor->photo_url,
            ] : null),
            'room' => $this->whenLoaded('room', fn () => $this->room?->name),
            'my_bookings' => $this->whenLoaded('bookings', fn () => $mine->map(fn (Booking $b) => [
                'id' => $b->id,
                'status' => $b->status,
                'payment_mode' => $b->payment_mode,
                'child_id' => $b->child_id,
            ])->values()),
        ];
    }
}
