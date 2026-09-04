<?php

namespace Database\Factories;

use App\Enums\ScoringMode;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarkSetting>
 */
class MarkSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...MarkSetting::defaults(),
            'school_id' => School::factory(),
        ];
    }

    public function division(): static
    {
        return $this->state([
            'scoring_mode' => ScoringMode::DivisionScore,
            'class_score_max' => 15,
            'home_assignment_max' => 15,
            'project_max' => 15,
            'class_test_max' => 15,
            'exam_allocation_percent' => 50,
        ]);
    }
}
