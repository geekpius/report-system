<?php

namespace Database\Factories;

use App\Models\ClassSubjectTeacher;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSubjectTeacher>
 */
class ClassSubjectTeacherFactory extends Factory
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
            'teacher_id' => Teacher::factory()->create(['school_id' => $schoolClass->school_id])->id,
        ];
    }
}
