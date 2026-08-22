<?php

namespace App\Http\Requests\Api\ClassSubject;

use App\Enums\Role;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $schoolClass = $this->route('schoolClass');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $schoolClass instanceof SchoolClass
            && $schoolClass->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolClass = $this->route('schoolClass');

        return [
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.subjectId' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists(Subject::class, 'id')->where('school_id', $school->id),
                Rule::unique(ClassSubject::class, 'subject_id')
                    ->where('school_class_id', $schoolClass->id),
            ],
            'subjects.*.isMandatory' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subjects.*.subjectId.unique' => 'One or more selected subjects are already assigned to this class.',
        ];
    }
}
