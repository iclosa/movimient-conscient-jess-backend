<?php

namespace App\Http\Resources\V1;

use App\Models\GratitudeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GratitudeEntry
 */
class GratitudeEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_date' => $this->entry_date->toDateString(),
            'body' => $this->body,
        ];
    }
}
