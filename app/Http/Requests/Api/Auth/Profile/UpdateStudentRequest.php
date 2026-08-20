<?php

namespace App\Http\Requests\Api\Auth\Profile;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Client;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the client is authorized to update this student.
     */
    public function authorize(): bool
    {
        $client = $this->user();
        $student = $this->route('student');

        return $client instanceof Client
            && $client->role === Role::Student
            && $student instanceof Student
            && $student->client_id === $client->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'admissionNumber' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Student::class, 'admission_number')
                    ->where('school_id', $student->school_id)
                    ->ignore($student->id),
            ],
            'dateOfBirth' => ['required', 'date', 'before:today'],
            'schoolClassId' => [
                'nullable',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $student->school_id),
            ],
        ];
    }
}
