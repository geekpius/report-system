<?php

namespace App\Http\Resources;

use App\Models\ClassSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassSubject
 */
class ClassSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schoolClassId' => $this->school_class_id,
            'subjectId' => $this->subject_id,
            'isMandatory' => $this->is_mandatory,
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
        ];
    }
}
