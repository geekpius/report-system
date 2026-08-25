<?php

namespace App\Http\Resources;

use App\Models\StudentTermResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentTermResult
 */
class StudentTermResultResource extends JsonResource
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
            'studentClassEnrollmentId' => $this->student_class_enrollment_id,
            'academicYearId' => $this->academic_year_id,
            'termId' => $this->term_id,
            'subjectsCount' => $this->subjects_count,
            'totalScore' => (float) $this->total_score,
            'averageScore' => (float) $this->average_score,
            'classPosition' => $this->class_position,
            'calculatedAt' => $this->calculated_at->toIso8601String(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'classEnrollment' => new StudentClassEnrollmentResource($this->whenLoaded('classEnrollment')),
            'academicYear' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
        ];
    }
}
