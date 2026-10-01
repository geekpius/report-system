<?php

namespace App\Http\Requests\Api\Subject;

use App\Enums\Role;
use App\Enums\SubjectStatus;
use App\Models\Client;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectStatusRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::enum(SubjectStatus::class)],
        ];
    }
}
