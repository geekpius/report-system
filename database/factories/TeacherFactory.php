<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory()->teacher(),
            'school_id' => School::factory(),
            'staff_number' => fake()->unique()->numerify('STF-####'),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
