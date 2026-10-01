<?php

namespace Database\Factories;

use App\Enums\SubjectStatus;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('SUB-###'),
            'status' => SubjectStatus::Active,
        ];
    }
}
