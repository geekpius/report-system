<?php

namespace App\Http\Requests\Api\ClassSubject;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListClassSubjectRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        if (! $this->has('isMandatory')) {
            return;
        }

        $this->merge([
            'isMandatory' => filter_var($this->input('isMandatory'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isMandatory' => ['sometimes', 'boolean'],
        ];
    }
}
