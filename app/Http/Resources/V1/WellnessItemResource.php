<?php

namespace App\Http\Resources\V1;

use App\Models\WellnessItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WellnessItem
 */
class WellnessItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'media_url' => $this->media_url,
            'duration_minutes' => $this->duration_minutes,
            'icon' => $this->icon,
            'config' => $this->config,
        ];
    }
}
