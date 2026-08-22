<?php

namespace Tests\Feature\Domain;

use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_subject_links_a_student_to_a_subject_for_an_enrollment(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'is_mandatory' => true,
        ]);

        $studentSubject = StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
        ]);

        $this->assertTrue(Str::isUuid($studentSubject->id));
        $this->assertSame(StudentSubjectStatus::Active, $studentSubject->status);
        $this->assertTrue($studentSubject->student->is($student));
        $this->assertTrue($studentSubject->subject->is($subject));
        $this->assertTrue($studentSubject->schoolClass->is($schoolClass));
        $this->assertTrue($studentSubject->classEnrollment->is($enrollment));
        $this->assertTrue($student->studentSubjects->contains($studentSubject));
        $this->assertTrue($enrollment->studentSubjects->contains($studentSubject));
        $this->assertTrue($subject->studentSubjects->contains($studentSubject));
        $this->assertTrue($schoolClass->studentSubjects->contains($studentSubject));
    }

    public function test_a_student_can_take_a_subject_only_once_per_enrollment(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);

        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
        ]);

        $this->expectException(QueryException::class);

        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
        ]);
    }

    public function test_student_subject_status_supports_dropped_and_completed(): void
    {
        $dropped = StudentSubject::factory()->dropped()->create();
        $completed = StudentSubject::factory()->completed()->create();

        $this->assertSame(StudentSubjectStatus::Dropped, $dropped->status);
        $this->assertSame(StudentSubjectStatus::Completed, $completed->status);
        $this->assertNull($dropped->classEnrollment->studentSubjects()->active()->first());
    }
}
