<?php

namespace App\Http\Requests\Api\ClassSubjectTeacher;

use App\Enums\Role;
use App\Models\ClassSubjectTeacher;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassSubjectTeacherRequest extends FormRequest
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
            'schoolClassId' => [
                'required',
                'uuid',
                Rule::exists(SchoolClass::class, 'id')->where('school_id', $school->id),
            ],
            'subjectIds' => ['required', 'array', 'min:1'],
            'subjectIds.*' => [
                'uuid',
                'distinct',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                Rule::unique(ClassSubjectTeacher::class, 'subject_id')
                    ->where('school_class_id', $this->input('schoolClassId')),
            ],
            'teacherId' => [
                'required',
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
            'subjectIds.*.unique' => 'One or more selected subjects already have a teacher assigned in this class.',
        ];
    }
}
