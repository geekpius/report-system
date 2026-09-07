<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassMarkRequest extends FormRequest
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
            'marks' => ['required', 'array', 'min:1'],
            ...$this->markIdentityRules($school, true, 'marks.*'),
            ...$this->classScoreRules($school, true, 'marks.*'),
            'marks.*.teacherId' => [
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
        return $this->markScoreMessages('marks.*');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectDuplicateMarkIdentities($validator);
        });
    }

    protected function rejectDuplicateMarkIdentities(Validator $validator): void
    {
        $marks = $this->input('marks');

        if (! is_array($marks)) {
            return;
        }

        $seen = [];

        foreach ($marks as $index => $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $key = implode('|', [
                $mark['studentClassEnrollmentId'] ?? '',
                $mark['subjectId'] ?? '',
                $mark['termId'] ?? '',
            ]);

            if ($key === '||') {
                continue;
            }

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "marks.{$index}.subjectId",
                    'A mark already exists for this student, subject, and term.',
                );

                continue;
            }

            $seen[$key] = true;
        }
    }
}
