<?php

namespace App\Http\Resources\V1;

use App\Models\Pass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pass
 */
class PassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'classes_count' => $this->classes_count,
            'unlimited' => $this->classes_count === null,
            'validity_days' => $this->validity_days,
            'price_cents' => $this->price_cents,
            'price_label' => $this->price_label,
            'is_featured' => $this->is_featured,
        ];
    }
}
