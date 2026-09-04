<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertExamMarkRequest extends FormRequest
{
    use ValidatesMarkIdentity;
    use ValidatesMarkScores;

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
            ...$this->markIdentityRules($school, false),
            ...$this->examScoreRules($school, true),
            'teacherId' => [
                'sometimes',
                'nullable',
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
            ...$this->markIdentityMessages(),
            ...$this->markScoreMessages(),
        ];
    }
}
