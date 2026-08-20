<?php

namespace App\Http\Resources;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin School
 */
class SchoolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'imageUrl' => $this->image_url,
            'phone' => $this->phone,
            'motto' => $this->motto,
            'email' => $this->email,
            'ownerId' => $this->owner_id,
            'owner' => new ClientResource($this->whenLoaded('owner')),
        ];
    }
}
