<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Mark;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassMarkRequest extends FormRequest
{
    use ValidatesMarkScores;

    public function authorize(): bool
    {
        $client = $this->user();
        $school = $this->route('school');
        $mark = $this->route('mark');

        return $client instanceof Client
            && $client->role === Role::Owner
            && $school instanceof School
            && $school->owner_id === $client->id
            && $mark instanceof Mark
            && $mark->school_id === $school->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $school = $this->route('school');

        return [
            ...$this->classScoreRules($school, false),
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
        return $this->markScoreMessages();
    }
}
