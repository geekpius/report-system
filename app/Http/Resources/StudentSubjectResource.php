<?php

namespace App\Http\Resources;

use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentSubject
 */
class StudentSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'studentId' => $this->student_id,
            'subjectId' => $this->subject_id,
            'schoolClassId' => $this->school_class_id,
            'studentClassEnrollmentId' => $this->student_class_enrollment_id,
            'status' => $this->status->value,
            'student' => new StudentResource($this->whenLoaded('student')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'classEnrollment' => new StudentClassEnrollmentResource($this->whenLoaded('classEnrollment')),
        ];
    }
}
