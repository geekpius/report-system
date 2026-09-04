<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Term;
use Closure;
use Illuminate\Validation\Rule;

trait ValidatesMarkIdentity
{
    /**
     * @return array<string, mixed>
     */
    protected function markIdentityRules(mixed $school, bool $unique): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $subjectRules = [
            'required',
            'uuid',
            Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
            function (string $attribute, mixed $value, Closure $fail): void {
                $enrollmentId = $this->input('studentClassEnrollmentId');
                $studentId = $this->input('studentId');

                if (! is_string($enrollmentId) || ! is_string($studentId) || ! is_string($value)) {
                    return;
                }

                $takesSubject = StudentSubject::query()
                    ->where('student_class_enrollment_id', $enrollmentId)
                    ->where('student_id', $studentId)
                    ->where('subject_id', $value)
                    ->where('status', StudentSubjectStatus::Active)
                    ->exists();

                if (! $takesSubject) {
                    $fail('The student does not have an active enrollment for this subject.');
                }
            },
        ];

        if ($unique) {
            $subjectRules[] = Rule::unique(Mark::class, 'subject_id')
                ->where('student_class_enrollment_id', $this->input('studentClassEnrollmentId'))
                ->where('term_id', $this->input('termId'));
        }

        return [
            'studentId' => [
                'required',
                'uuid',
                Rule::exists(Student::class, 'id')->where('school_id', $school->id),
            ],
            'subjectId' => $subjectRules,
            'schoolClassId' => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            'studentClassEnrollmentId' => [
                'required',
                'uuid',
                Rule::exists(StudentClassEnrollment::class, 'id')
                    ->where('student_id', $this->input('studentId'))
                    ->where('school_class_id', $this->input('schoolClassId'))
                    ->where('academic_year_id', $this->input('academicYearId'))
                    ->where('status', EnrollmentStatus::Active->value),
            ],
            'academicYearId' => [
                'required',
                'uuid',
                Rule::exists(AcademicYear::class, 'id')->where('school_id', $school->id),
            ],
            'termId' => [
                'required',
                'uuid',
                Rule::exists(Term::class, 'id')->where('academic_year_id', $this->input('academicYearId')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function markIdentityMessages(): array
    {
        return [
            'subjectId.unique' => 'A mark already exists for this student, subject, and term.',
            'studentClassEnrollmentId.exists' => 'The selected enrollment must be an active enrollment for this student and class.',
        ];
    }
}
