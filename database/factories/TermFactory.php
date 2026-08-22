<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 3);
        $startsOn = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Term '.$number,
            'number' => $number,
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+3 months'),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
