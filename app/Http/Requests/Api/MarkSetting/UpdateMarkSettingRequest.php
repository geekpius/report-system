<?php

namespace App\Http\Requests\Api\MarkSetting;

use App\Enums\Role;
use App\Enums\ScoringMode;
use App\Models\Client;
use App\Models\School;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarkSettingRequest extends FormRequest
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
        return [
            'scoringMode' => ['required', Rule::enum(ScoringMode::class)],
            'totalScore' => ['required', 'array'],
            'totalScore.classScorePercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'totalScore.examScorePercent' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $classScorePercent = $this->input('totalScore.classScorePercent');

                    if (! is_numeric($classScorePercent) || round((float) $classScorePercent + (float) $value, 2) !== 100.0) {
                        $fail('The class score percent and exam score percent must add up to 100.');
                    }
                },
            ],
            'divisionScore' => ['required', 'array'],
            'divisionScore.classScoreMax' => ['required', 'numeric', 'min:0'],
            'divisionScore.homeAssignmentMax' => ['required', 'numeric', 'min:0'],
            'divisionScore.projectMax' => ['required', 'numeric', 'min:0'],
            'divisionScore.classTestMax' => ['required', 'numeric', 'min:0'],
            'divisionScore.examAllocationPercent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
