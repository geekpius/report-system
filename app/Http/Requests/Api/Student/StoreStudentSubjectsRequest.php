<?php

namespace App\Http\Requests\Api\Student;

use App\Enums\Role;
use App\Enums\StudentSubjectStatus;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $student = $this->route('student');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $student instanceof Student
            && $student->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $student = $this->route('student');
        $enrollment = $student->activeClassEnrollment;

        return [
            'subjectIds' => ['required', 'array', 'min:1'],
            'subjectIds.*' => [
                'uuid',
                'distinct',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                function (string $attribute, mixed $value, Closure $fail) use ($enrollment): void {
                    if ($enrollment === null) {
                        $fail('The student does not have an active class enrollment.');

                        return;
                    }

                    $isElective = ClassSubject::query()
                        ->where('school_class_id', $enrollment->school_class_id)
                        ->where('subject_id', $value)
                        ->where('is_mandatory', false)
                        ->exists();

                    if (! $isElective) {
                        $fail('One or more selected subjects are not offered as electives for this class.');

                        return;
                    }

                    $existing = StudentSubject::query()
                        ->where('student_class_enrollment_id', $enrollment->id)
                        ->where('subject_id', $value)
                        ->first();

                    if ($existing !== null && $existing->status !== StudentSubjectStatus::Dropped) {
                        $fail('One or more selected subjects are already assigned to this student.');
                    }
                },
            ],
        ];
    }
}
