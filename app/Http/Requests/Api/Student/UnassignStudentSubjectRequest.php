<?php

namespace App\Http\Requests\Api\Student;

use App\Enums\Role;
use App\Enums\StudentSubjectStatus;
use App\Models\Client;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UnassignStudentSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $student = $this->route('student');
        $studentSubject = $this->route('studentSubject');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $student instanceof Student
            && $student->school_id === $school->id
            && $studentSubject instanceof StudentSubject
            && $studentSubject->student_id === $student->id
            && $studentSubject->status === StudentSubjectStatus::Active;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
