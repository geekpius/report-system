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
    protected function classScoreRules(mixed $school, bool $required, string $prefix = ''): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $setting = MarkSetting::resolveForSchool($school);
        $presence = $required ? 'required' : 'sometimes';
        $field = fn (string $name): string => $this->markField($name, $prefix);

        if ($setting->scoring_mode === ScoringMode::TotalScore) {
            return [
                $field('classScore') => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_score_percent)],
                $field('homeAssignmentScore') => ['sometimes', 'numeric', 'min:0', 'max:0'],
                $field('projectScore') => ['sometimes', 'numeric', 'min:0', 'max:0'],
                $field('classTestScore') => ['sometimes', 'numeric', 'min:0', 'max:0'],
            ];
        }

        return [
            $field('classScore') => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_score_max)],
            $field('homeAssignmentScore') => [$presence, 'numeric', 'min:0', $this->maxRule($setting->home_assignment_max)],
            $field('projectScore') => [$presence, 'numeric', 'min:0', $this->maxRule($setting->project_max)],
            $field('classTestScore') => [$presence, 'numeric', 'min:0', $this->maxRule($setting->class_test_max)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function examScoreRules(mixed $school, bool $required, string $prefix = ''): array
    {
        if (! $school instanceof School) {
            return [];
        }

        $field = fn (string $name): string => $this->markField($name, $prefix);

        return [
            $field('participated') => [$required ? 'required' : 'sometimes', 'boolean'],
            $field('examScore') => [
                $required ? 'required_if:'.$field('participated').',true' : 'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function markScoreMessages(string $prefix = ''): array
    {
        $field = fn (string $name): string => $this->markField($name, $prefix);

        return [
            $field('classScore').'.max' => 'The class score may not be greater than the active mark setting allows.',
            $field('homeAssignmentScore').'.max' => 'The home assignment score may not be greater than the active mark setting allows.',
            $field('projectScore').'.max' => 'The project score may not be greater than the active mark setting allows.',
            $field('classTestScore').'.max' => 'The class test score may not be greater than the active mark setting allows.',
            $field('examScore').'.max' => 'The exam score may not be greater than 100.',
        ];
    }

    protected function maxRule(mixed $max): string
    {
        return 'max:'.(float) $max;
    }

    protected function markField(string $name, string $prefix = ''): string
    {
        return $prefix === '' ? $name : "{$prefix}.{$name}";
    }
}
