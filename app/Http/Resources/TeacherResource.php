<?php

namespace App\Http\Resources;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Teacher
 */
class TeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'schoolId' => $this->school_id,
            'staffNumber' => $this->staff_number,
            'phone' => $this->phone,
            'client' => new ClientResource($this->whenLoaded('client')),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'classes' => SchoolClassResource::collection($this->whenLoaded('classes')),
        ];
    }
}
