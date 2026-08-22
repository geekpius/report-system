<?php

namespace Database\Factories;

use App\Enums\StudentSubjectStatus;
use App\Models\ClassSubject;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentSubject>
 */
class StudentSubjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $enrollment = StudentClassEnrollment::factory()->create();
        $classSubject = ClassSubject::factory()->create([
            'school_class_id' => $enrollment->school_class_id,
        ]);

        return [
            'student_id' => $enrollment->student_id,
            'subject_id' => $classSubject->subject_id,
            'school_class_id' => $enrollment->school_class_id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ];
    }

    public function dropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentSubjectStatus::Dropped,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StudentSubjectStatus::Completed,
        ]);
    }
}
