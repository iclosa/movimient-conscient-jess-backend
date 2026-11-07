<?php

namespace App\Http\Resources\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Article del blog. `body` és text pla: paràgrafs separats per una línia buida,
 * "## " per als subtítols i [text](/enllaç/) per als enllaços.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'published_at' => ($this->published_at ?? $this->created_at)?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'image_url' => $this->image_url,
            'translations' => [
                'ca' => $this->contentFor('ca'),
                'es' => $this->contentFor('es'),
            ],
        ];
    }
}
