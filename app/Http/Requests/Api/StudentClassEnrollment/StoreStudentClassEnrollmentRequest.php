<?php

namespace App\Http\Requests\Api\StudentClassEnrollment;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentClassEnrollmentRequest extends FormRequest
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

        return [
            'schoolClassId' => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            'academicYearId' => [
                'required',
                'uuid',
                Rule::exists(AcademicYear::class, 'id')->where('school_id', $school->id),
                Rule::unique(StudentClassEnrollment::class, 'academic_year_id')
                    ->where('student_id', $student->id),
            ],
            'startedAt' => ['sometimes', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'academicYearId.unique' => 'This student already has a class enrollment for the selected academic year.',
        ];
    }
}
