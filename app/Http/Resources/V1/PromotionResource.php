<?php

namespace App\Http\Resources\V1;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Promotion
 */
class PromotionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'color' => $this->color,
            'image_url' => $this->image_url,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'ends_at' => $this->ends_at?->toIso8601String(),
        ];
    }
}
