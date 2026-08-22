<?php

namespace App\Http\Requests\Api\StudentSubject;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListStudentSubjectRequest extends FormRequest
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
            && $enrollment->student_id === $student->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
