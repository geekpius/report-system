<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_assign_elective_subjects_to_a_student_enrollment(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $french = Subject::factory()->create(['school_id' => $school->id, 'name' => 'French']);
        $music = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Music']);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $french->id,
            'is_mandatory' => false,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $music->id,
            'is_mandatory' => false,
        ]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.subjects.store', [$school, $student, $enrollment]), [
                'subjects' => [
                    ['subjectId' => $french->id],
                    ['subjectId' => $music->id],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subject.name', 'French')
            ->assertJsonPath('data.1.subject.name', 'Music');

        $this->assertDatabaseHas('student_subjects', [
            'student_class_enrollment_id' => $enrollment->id,
            'subject_id' => $french->id,
        ]);
    }

    public function test_owners_can_list_subjects_for_a_student_enrollment(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
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
        StudentSubject::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.students.class-enrollments.subjects.index', [$school, $student, $enrollment]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject.name', 'Mathematics');
    }

    public function test_owners_cannot_assign_mandatory_class_subjects_as_electives(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'is_mandatory' => true,
        ]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.subjects.store', [$school, $student, $enrollment]), [
                'subjects' => [
                    ['subjectId' => $mathematics->id],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjects.0.subjectId']);
    }

    public function test_owners_cannot_assign_subjects_to_a_non_active_enrollment(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $french = Subject::factory()->create(['school_id' => $school->id]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $french->id,
            'is_mandatory' => false,
        ]);
        $enrollment = StudentClassEnrollment::factory()->promoted()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.students.class-enrollments.subjects.store', [$school, $student, $enrollment]), [
                'subjects' => [
                    ['subjectId' => $french->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_owners_cannot_manage_subjects_for_another_students_enrollment(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $otherStudent = Student::factory()->create(['school_id' => $school->id]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $otherStudent->id,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.students.class-enrollments.subjects.index', [$school, $student, $enrollment]))
            ->assertForbidden();
    }
}
