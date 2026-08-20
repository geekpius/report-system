<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Client;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => null,
            'school_id' => School::factory(),
            'school_class_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(Gender::cases()),
            'admission_number' => fake()->unique()->numerify('ADM-####'),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-10 years')->format('Y-m-d'),
        ];
    }

    public function withClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => Client::factory()->student(),
        ]);
    }
}
