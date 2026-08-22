<?php

namespace App\Http\Resources;

use App\Models\ClassSubjectTeacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassSubjectTeacher
 */
class ClassSubjectTeacherResource extends JsonResource
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
            'teacherId' => $this->teacher_id,
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
        ];
    }
}
