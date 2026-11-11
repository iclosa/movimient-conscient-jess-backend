<?php

namespace App\Http\Resources\V1;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
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
            'sent_at' => $this->sent_at?->toIso8601String(),
            'personal' => $this->user_id !== null,
            'read' => $this->read_at !== null,
        ];
    }
}
