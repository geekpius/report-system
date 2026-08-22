<?php

namespace App\Http\Resources;

use App\Models\Aggregate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Aggregate
 */
class AggregateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schoolId' => $this->school_id,
            'minScore' => $this->min_score,
            'maxScore' => $this->max_score,
            'grade' => $this->grade,
            'remarks' => $this->remarks,
            'school' => new SchoolResource($this->whenLoaded('school')),
        ];
    }
}
