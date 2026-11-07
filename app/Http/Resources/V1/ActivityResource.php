<?php

namespace App\Http\Resources\V1;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'level' => $this->level,
            'color' => $this->color,
            'color_text' => $this->color_text,
            'price_cents' => $this->price_cents ?: null,
            'price_label' => $this->price_label,
            'image_url' => $this->image_url,
            'is_family' => $this->is_family,
        ];
    }
}
