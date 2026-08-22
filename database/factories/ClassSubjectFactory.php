<?php

namespace Database\Factories;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSubject>
 */
class ClassSubjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $schoolClass = SchoolClass::factory()->create();

        return [
            'school_class_id' => $schoolClass->id,
            'subject_id' => Subject::factory()->create(['school_id' => $schoolClass->school_id])->id,
            'is_mandatory' => true,
        ];
    }

    public function elective(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => false,
        ]);
    }
}
