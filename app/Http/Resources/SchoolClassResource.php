<?php

namespace App\Http\Resources;

use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SchoolClass
 */
class SchoolClassResource extends JsonResource
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
            'alias' => $this->alias,
            'classTeacherId' => $this->class_teacher_id,
            'school' => new SchoolResource($this->whenLoaded('school')),
            'classTeacher' => new TeacherResource($this->whenLoaded('classTeacher')),
            'students' => StudentResource::collection($this->whenLoaded('students')),
        ];
    }
}
