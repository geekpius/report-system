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
    protected function markIdentityRules(mixed $school, bool $unique, string $prefix = ''): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $field = fn (string $name): string => $this->markField($name, $prefix);

        return [
            $field('studentId') => [
                'required',
                'uuid',
                Rule::exists(Student::class, 'id')->where('school_id', $school->id),
            ],
            $field('subjectId') => [
                'required',
                'uuid',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                function (string $attribute, mixed $value, Closure $fail) use ($unique): void {
                    $enrollmentId = $this->siblingValue($attribute, 'studentClassEnrollmentId');
                    $studentId = $this->siblingValue($attribute, 'studentId');
                    $termId = $this->siblingValue($attribute, 'termId');

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

                    if (! $unique || ! is_string($termId)) {
                        return;
                    }

                    $alreadyExists = Mark::query()
                        ->where('student_class_enrollment_id', $enrollmentId)
                        ->where('subject_id', $value)
                        ->where('term_id', $termId)
                        ->exists();

                    if ($alreadyExists) {
                        $fail('A mark already exists for this student, subject, and term.');
                    }
                },
            ],
            $field('schoolClassId') => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            $field('studentClassEnrollmentId') => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $exists = StudentClassEnrollment::query()
                        ->whereKey($value)
                        ->where('student_id', $this->siblingValue($attribute, 'studentId'))
                        ->where('school_class_id', $this->siblingValue($attribute, 'schoolClassId'))
                        ->where('academic_year_id', $this->siblingValue($attribute, 'academicYearId'))
                        ->where('status', EnrollmentStatus::Active)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected enrollment must be an active enrollment for this student and class.');
                    }
                },
            ],
            $field('academicYearId') => [
                'required',
                'uuid',
                Rule::exists(AcademicYear::class, 'id')->where('school_id', $school->id),
            ],
            $field('termId') => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $exists = Term::query()
                        ->whereKey($value)
                        ->where('academic_year_id', $this->siblingValue($attribute, 'academicYearId'))
                        ->exists();

                    if (! $exists) {
                        $fail('The selected term is invalid for the given academic year.');
                    }
                },
            ],
        ];
    }

    protected function siblingValue(string $attribute, string $name): mixed
    {
        $segments = explode('.', $attribute);
        $segments[array_key_last($segments)] = $name;

        return $this->input(implode('.', $segments));
    }
}
