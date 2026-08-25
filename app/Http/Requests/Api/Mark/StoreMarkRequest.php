<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'studentId' => [
                'required',
                'uuid',
                Rule::exists(Student::class, 'id')->where('school_id', $school->id),
            ],
            'subjectId' => [
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
                Rule::unique(Mark::class, 'subject_id')
                    ->where('student_class_enrollment_id', $this->input('studentClassEnrollmentId'))
                    ->where('term_id', $this->input('termId')),
            ],
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
            'classScore' => ['required', 'numeric', 'min:0', 'max:15'],
            'homeAssignmentScore' => ['required', 'numeric', 'min:0', 'max:15'],
            'projectScore' => ['required', 'numeric', 'min:0', 'max:15'],
            'classTestScore' => ['required', 'numeric', 'min:0', 'max:15'],
            'examScore' => ['required', 'numeric', 'min:0', 'max:100'],
            'teacherId' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists(Teacher::class, 'id')->where('school_id', $school->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subjectId.unique' => 'A mark already exists for this student, subject, and term.',
            'studentClassEnrollmentId.exists' => 'The selected enrollment must be an active enrollment for this student and class.',
        ];
    }
}
