<?php

namespace App\Http\Resources;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademicYear
 */
class AcademicYearResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schoolId' => $this->school_id,
            'name' => $this->name,
            'startsOn' => $this->starts_on->toDateString(),
            'endsOn' => $this->ends_on->toDateString(),
            'isCurrent' => $this->is_current,
            'school' => new SchoolResource($this->whenLoaded('school')),
            'terms' => TermResource::collection($this->whenLoaded('terms')),
        ];
    }
}
