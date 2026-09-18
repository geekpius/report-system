<?php

namespace Database\Factories;

use App\Enums\SchoolStatus;
use App\Enums\SchoolType;
use App\Models\Client;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' School',
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'type' => fake()->randomElement(SchoolType::cases()),
            'logo_url' => null,
            'stamp_url' => null,
            'signature_url' => null,
            'phone' => fake()->phoneNumber(),
            'motto' => fake()->optional()->sentence(4),
            'email' => fake()->optional()->companyEmail(),
            'status' => SchoolStatus::Active,
            'in_session' => true,
            'owner_id' => Client::factory()->owner(),
        ];
    }
}
