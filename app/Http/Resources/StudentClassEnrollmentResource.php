<?php

namespace App\Http\Resources;

use App\Models\StudentClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentClassEnrollment
 */
class StudentClassEnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'studentId' => $this->student_id,
            'schoolClassId' => $this->school_class_id,
            'academicYearId' => $this->academic_year_id,
            'status' => $this->status->value,
            'startedAt' => $this->started_at->toIso8601String(),
            'endedAt' => $this->ended_at?->toIso8601String(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'academicYear' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'studentSubjects' => StudentSubjectResource::collection($this->whenLoaded('studentSubjects')),
        ];
    }
}
