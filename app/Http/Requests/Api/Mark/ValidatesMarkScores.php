<?php

namespace App\Http\Requests\Api\Mark;

use App\Enums\ScoringMode;
use App\Models\MarkSetting;
use App\Models\School;

trait ValidatesMarkScores
{
    /**
     * @return array<string, mixed>
     */
    protected function classScoreRules(mixed $school, bool $required): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $setting = MarkSetting::resolveForSchool($school);
        $presence = $required ? 'required' : 'sometimes';

        if ($setting->scoring_mode === ScoringMode::TotalScore) {
            return [
                'classScore' => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_score_percent)],
                'homeAssignmentScore' => ['sometimes', 'numeric', 'min:0', 'max:0'],
                'projectScore' => ['sometimes', 'numeric', 'min:0', 'max:0'],
                'classTestScore' => ['sometimes', 'numeric', 'min:0', 'max:0'],
            ];
        }

        return [
            'classScore' => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_score_max)],
            'homeAssignmentScore' => [$presence, 'numeric', 'min:0', $this->maxRule($setting->home_assignment_max)],
            'projectScore' => [$presence, 'numeric', 'min:0', $this->maxRule($setting->project_max)],
            'classTestScore' => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_test_max)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function examScoreRules(mixed $school, bool $required): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $participatedRule = [$required ? 'required' : 'sometimes', 'boolean'];
        $examRequired = $required && $this->boolean('participated');
        $presence = $examRequired ? 'required' : 'sometimes';

        return [
            'participated' => $participatedRule,
            'examScore' => [$presence, 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function markScoreMessages(): array
    {
        return [
            'classScore.max' => 'The class score may not be greater than the active mark setting allows.',
            'homeAssignmentScore.max' => 'The home assignment score may not be greater than the active mark setting allows.',
            'projectScore.max' => 'The project score may not be greater than the active mark setting allows.',
            'classTestScore.max' => 'The class test score may not be greater than the active mark setting allows.',
            'examScore.max' => 'The exam score may not be greater than 100.',
        ];
    }

    protected function maxRule(mixed $max): string
    {
        return 'max:'.(float) $max;
    }
}
