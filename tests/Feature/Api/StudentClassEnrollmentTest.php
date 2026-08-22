<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClassEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_assign_a_student_to_a_class_for_an_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026']);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'is_mandatory' => true,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $english->id,
            'is_mandatory' => true,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.store', [$school, $student]), [
                'schoolClassId' => $schoolClass->id,
                'academicYearId' => $academicYear->id,
                'startedAt' => '2025-09-01',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', EnrollmentStatus::Active->value)
            ->assertJsonPath('data.schoolClassId', $schoolClass->id)
            ->assertJsonPath('data.academicYearId', $academicYear->id)
            ->assertJsonPath('data.schoolClass.name', 'JHS 1')
            ->assertJsonCount(2, 'data.studentSubjects');

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active->value,
        ]);
        $this->assertSame($schoolClass->id, $student->fresh()->school_class_id);
        $this->assertDatabaseHas('student_subjects', [
            'student_id' => $student->id,
            'subject_id' => $mathematics->id,
            'status' => StudentSubjectStatus::Active->value,
        ]);
    }

    public function test_owners_can_list_a_students_class_enrollments(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1']);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026']);
        StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
        StudentClassEnrollment::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.students.class-enrollments.index', [$school, $student]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.schoolClass.name', 'JHS 1')
            ->assertJsonPath('data.0.academicYear.name', '2025/2026');
    }

    public function test_owners_cannot_create_duplicate_enrollments_for_the_same_academic_year(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.store', [$school, $student]), [
                'schoolClassId' => $schoolClass->id,
                'academicYearId' => $academicYear->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['academicYearId']);
    }

    public function test_owners_cannot_manage_enrollments_for_a_student_in_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $otherSchool->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.students.class-enrollments.index', [$school, $student]))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.store', [$school, $student]), [
                'schoolClassId' => $schoolClass->id,
                'academicYearId' => $academicYear->id,
            ])
            ->assertForbidden();
    }

    public function test_enrollment_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.store', [$school, $student]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['schoolClassId', 'academicYearId']);
    }
}
