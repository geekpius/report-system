<?php

namespace App\Http\Requests\Api\StudentSubject;

use App\Enums\EnrollmentStatus;
use App\Enums\Role;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $student = $this->route('student');
        $enrollment = $this->route('studentClassEnrollment');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $student instanceof Student
            && $student->school_id === $school->id
            && $enrollment instanceof StudentClassEnrollment
            && $enrollment->student_id === $student->id
            && $enrollment->status === EnrollmentStatus::Active;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $enrollment = $this->route('studentClassEnrollment');

        return [
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.subjectId' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                function (string $attribute, mixed $value, Closure $fail) use ($enrollment): void {
                    $isElective = ClassSubject::query()
                        ->where('school_class_id', $enrollment->school_class_id)
                        ->where('subject_id', $value)
                        ->where('is_mandatory', false)
                        ->exists();

                    if (! $isElective) {
                        $fail('One or more selected subjects are not offered as electives for this class.');
                    }
                },
                Rule::unique(StudentSubject::class, 'subject_id')
                    ->where('student_class_enrollment_id', $enrollment->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subjects.*.subjectId.unique' => 'One or more selected subjects are already assigned to this enrollment.',
        ];
    }
}
