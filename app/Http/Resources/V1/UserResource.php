<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'birth_date' => $this->birth_date?->toDateString(),
            'marketing_consent' => $this->marketing_consent,
            'children' => ChildResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
