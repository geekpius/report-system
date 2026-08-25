<?php

namespace App\Http\Resources;

use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mark
 */
class MarkResource extends JsonResource
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
            'academicYearId' => $this->academic_year_id,
            'termId' => $this->term_id,
            'classScore' => (float) $this->class_score,
            'homeAssignmentScore' => (float) $this->home_assignment_score,
            'projectScore' => (float) $this->project_score,
            'classTestScore' => (float) $this->class_test_score,
            'continuousAssessmentScore' => (float) $this->continuous_assessment_score,
            'continuousAssessmentContribution' => (float) $this->continuous_assessment_contribution,
            'examScore' => (float) $this->exam_score,
            'examContribution' => (float) $this->exam_contribution,
            'totalScore' => (float) $this->total_score,
            'grade' => $this->grade,
            'gradeRemark' => $this->grade_remark,
            'teacherId' => $this->teacher_id,
            'student' => new StudentResource($this->whenLoaded('student')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'schoolClass' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'classEnrollment' => new StudentClassEnrollmentResource($this->whenLoaded('classEnrollment')),
            'academicYear' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
        ];
    }
}
