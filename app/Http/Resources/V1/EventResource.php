<?php

namespace App\Http\Resources\V1;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mine = $this->relationLoaded('registrations') ? $this->registrations->first() : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'capacity' => $this->capacity,
            'spots_left' => $this->spots_left,
            'price_cents' => $this->price_cents,
            'price_label' => $this->price_label,
            'image_url' => $this->image_url,
            'my_registration' => $mine?->status,
        ];
    }
}
