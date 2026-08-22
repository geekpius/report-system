<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentClassEnrollment>
 */
class StudentClassEnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $school = School::factory()->create();

        return [
            'student_id' => Student::factory()->create(['school_id' => $school->id])->id,
            'school_class_id' => SchoolClass::factory()->create(['school_id' => $school->id])->id,
            'academic_year_id' => AcademicYear::factory()->create(['school_id' => $school->id])->id,
            'status' => EnrollmentStatus::Active,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function promoted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Promoted,
            'ended_at' => now(),
        ]);
    }

    public function repeated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Repeated,
            'ended_at' => now(),
        ]);
    }
}
