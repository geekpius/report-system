<?php

namespace App\Http\Resources;

use App\Models\Mark;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{studentSubject: StudentSubject, academicYearId: string, termId: string, mark: Mark|null} $resource
 */
class MarkEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $studentSubject = $this->resource['studentSubject'];

        return [
            'studentId' => $studentSubject->student_id,
            'subjectId' => $studentSubject->subject_id,
            'schoolClassId' => $studentSubject->school_class_id,
            'studentClassEnrollmentId' => $studentSubject->student_class_enrollment_id,
            'academicYearId' => $this->resource['academicYearId'],
            'termId' => $this->resource['termId'],
            'student' => new StudentResource($studentSubject->student),
            'mark' => $this->resource['mark'] === null
                ? null
                : new MarkEntryMarkResource($this->resource['mark']),
        ];
    }
}
