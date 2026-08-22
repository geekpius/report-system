<?php

namespace Tests\Feature\Domain;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentClassEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_class_enrollment_tracks_class_history_for_an_academic_year(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026']);

        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
            'started_at' => '2025-09-01 08:00:00',
        ]);

        $this->assertTrue(Str::isUuid($enrollment->id));
        $this->assertSame(EnrollmentStatus::Active, $enrollment->status);
        $this->assertNull($enrollment->ended_at);
        $this->assertTrue($enrollment->student->is($student));
        $this->assertTrue($enrollment->schoolClass->is($schoolClass));
        $this->assertTrue($enrollment->academicYear->is($academicYear));
        $this->assertTrue($student->classEnrollments->contains($enrollment));
        $this->assertTrue($student->activeClassEnrollment->is($enrollment));
        $this->assertTrue($schoolClass->classEnrollments->contains($enrollment));
        $this->assertTrue($academicYear->classEnrollments->contains($enrollment));
    }

    public function test_a_student_can_have_only_one_enrollment_per_academic_year(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $firstClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $secondClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);

        StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $firstClass->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->expectException(QueryException::class);

        StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $secondClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }

    public function test_enrollment_status_supports_promoted_transferred_and_repeated(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $firstYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2024/2025']);
        $secondYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026']);
        $thirdYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026/2027']);

        $promoted = StudentClassEnrollment::factory()->promoted()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $firstYear->id,
        ]);
        $transferred = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $secondYear->id,
            'status' => EnrollmentStatus::Transferred,
            'ended_at' => now(),
        ]);
        $repeated = StudentClassEnrollment::factory()->repeated()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $thirdYear->id,
        ]);

        $this->assertSame(EnrollmentStatus::Promoted, $promoted->status);
        $this->assertSame(EnrollmentStatus::Transferred, $transferred->status);
        $this->assertSame(EnrollmentStatus::Repeated, $repeated->status);
        $this->assertNull($student->fresh()->activeClassEnrollment);
    }
}
