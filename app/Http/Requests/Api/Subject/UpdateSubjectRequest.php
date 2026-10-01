<?php

namespace App\Http\Requests\Api\Subject;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $subject = $this->route('subject');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $subject instanceof Subject
            && $subject->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $subject = $this->route('subject');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Subject::class, 'name')
                    ->where('school_id', $school->id)
                    ->ignore($subject->id),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Subject::class, 'code')
                    ->where('school_id', $school->id)
                    ->ignore($subject->id),
            ],
        ];
    }
}
