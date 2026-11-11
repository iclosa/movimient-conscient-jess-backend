<?php

namespace App\Http\Resources\V1;

use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inscripció d'una persona a un taller, retir o esdeveniment, amb el seu cobrament.
 *
 * @mixin EventRegistration
 */
class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;

        return [
            'id' => $this->id,
            'status' => $this->status,
            ...Payment::apiFields($this->payment),
            'can_cancel' => $this->status !== 'cancelled' && $event !== null && $event->starts_at->isFuture(),
            'event' => $event ? new EventResource($event) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
