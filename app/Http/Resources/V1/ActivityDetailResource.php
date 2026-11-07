<?php

namespace App\Http\Resources\V1;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activitat amb tot el contingut públic de la web: textos en català i castellà, preu, color i relacionades.
 *
 * @mixin Activity
 */
class ActivityDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => ['ca' => $this->slug, 'es' => $this->slug_es ?: $this->slug],
            'color' => $this->color,
            'color_text' => $this->color_text,
            'price_cents' => $this->price_cents ?: null,
            'price_label' => $this->price_label,
            'is_family' => $this->is_family,
            'image_url' => $this->image_url,
            'translations' => [
                'ca' => $this->contentFor('ca'),
                'es' => $this->contentFor('es'),
            ],
            'related' => $this->relationLoaded('siblings') ? $this->relatedIds($this->getRelation('siblings')) : [],
        ];
    }
}
