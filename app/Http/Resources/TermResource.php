<?php

namespace App\Http\Resources;

use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Term
 */
class TermResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academicYearId' => $this->academic_year_id,
            'name' => $this->name,
            'number' => $this->number,
            'startsOn' => $this->starts_on->toDateString(),
            'endsOn' => $this->ends_on->toDateString(),
            'isCurrent' => $this->is_current,
            'academicYear' => new AcademicYearResource($this->whenLoaded('academicYear')),
        ];
    }
}
