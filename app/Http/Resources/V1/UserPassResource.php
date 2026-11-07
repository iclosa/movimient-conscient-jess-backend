<?php

namespace App\Http\Resources\V1;

use App\Models\UserPass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserPass
 */
class UserPassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'classes_total' => $this->classes_total,
            'classes_remaining' => $this->classes_remaining,
            'unlimited' => $this->isUnlimited(),
            'starts_on' => $this->starts_on->toDateString(),
            'expires_on' => $this->expires_on?->toDateString(),
            'status' => $this->status,
            'usable' => $this->isUsable(),
        ];
    }
}
