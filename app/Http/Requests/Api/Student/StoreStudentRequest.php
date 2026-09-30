<?php

namespace App\Http\Requests\Api\Student;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
            'admissionNumber' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Student::class, 'admission_number')->where('school_id', $school->id),
            ],
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'dateOfBirth' => ['required', 'date', 'before:today'],
            'schoolClassId' => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            'academicYearId' => [
                'required',
                'uuid',
                Rule::exists(AcademicYear::class, 'id')->where('school_id', $school->id),
            ],
            'electiveSubjectIds' => ['sometimes', 'array'],
            'electiveSubjectIds.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $schoolClassId = $this->input('schoolClassId');

                    if (! is_string($schoolClassId)) {
                        return;
                    }

                    $isElective = ClassSubject::query()
                        ->where('school_class_id', $schoolClassId)
                        ->where('subject_id', $value)
                        ->where('is_mandatory', false)
                        ->exists();

                    if (! $isElective) {
                        $fail('One or more selected subjects are not offered as electives for this class.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admissionNumber.unique' => 'A student with this admission number already exists in this school.',
        ];
    }
}
