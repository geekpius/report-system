<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mark>
 */
class MarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);

        return [
            'school_id' => $school->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'participated' => true,
            'class_score' => 12,
            'home_assignment_score' => 14,
            'project_score' => 13,
            'class_test_score' => 15,
            'exam_score' => 80,
            'teacher_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Mark $mark): void {
            if ($mark->student_id === null) {
                return;
            }

            $schoolId = Student::query()->whereKey($mark->student_id)->value('school_id');

            if (is_string($schoolId)) {
                $mark->school_id = $schoolId;
            }
        });
    }
}
