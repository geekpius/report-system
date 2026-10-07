<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentSubjectStatus;
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

class StudentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{owner: Client, school: School, token: string}
     */
    protected function ownerContext(): array
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        return compact('owner', 'school', 'token');
    }

    public function test_owners_can_admit_a_student_with_elective_subjects(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        $academicYear = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $french = Subject::factory()->create(['school_id' => $school->id, 'name' => 'French']);
        $ict = Subject::factory()->create(['school_id' => $school->id, 'name' => 'ICT']);

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
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $french->id,
            'is_mandatory' => false,
        ]);
        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $ict->id,
            'is_mandatory' => false,
        ]);

        $this->withToken($token)
            ->postJson(route('api.schools.students.store', $school), [
                'admissionNumber' => 'ADM-2026-001',
                'firstName' => 'John',
                'middleName' => 'Kwame',
                'lastName' => 'Doe',
                'gender' => Gender::Male->value,
                'dateOfBirth' => '2012-04-15',
                'schoolClassId' => $schoolClass->id,
                'electiveSubjectIds' => [$french->id, $ict->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.admissionNumber', 'ADM-2026-001')
            ->assertJsonPath('data.firstName', 'John')
            ->assertJsonPath('data.middleName', 'Kwame')
            ->assertJsonPath('data.lastName', 'Doe')
            ->assertJsonPath('data.gender', Gender::Male->value)
            ->assertJsonPath('data.dateOfBirth', '2012-04-15')
            ->assertJsonPath('data.schoolClassId', $schoolClass->id)
            ->assertJsonMissingPath('data.schoolClass')
            ->assertJsonMissingPath('data.activeClassEnrollment');

        $student = Student::query()->where('admission_number', 'ADM-2026-001')->first();
        $this->assertNotNull($student);

        $this->assertDatabaseHas('student_class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Active->value,
        ]);

        foreach ([$mathematics, $english, $french, $ict] as $subject) {
            $this->assertDatabaseHas('student_subjects', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'status' => StudentSubjectStatus::Active->value,
            ]);
        }
    }

    public function test_owners_cannot_admit_students_with_mandatory_subjects_as_electives(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $academicYear = AcademicYear::factory()->current()->create(['school_id' => $school->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id]);

        ClassSubject::factory()->create([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $mathematics->id,
            'is_mandatory' => true,
        ]);

        $this->withToken($token)
            ->postJson(route('api.schools.students.store', $school), [
                'admissionNumber' => 'ADM-2026-001',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'gender' => Gender::Male->value,
                'dateOfBirth' => '2012-04-15',
                'schoolClassId' => $schoolClass->id,
                'electiveSubjectIds' => [$mathematics->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['electiveSubjectIds.0']);
    }

    public function test_owners_can_list_students_for_their_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-2002',
        ]);
        Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-1001',
        ]);
        Student::factory()->create(['admission_number' => 'ADM-9999']);

        $this->withToken($token)
            ->getJson(route('api.schools.students.index', $school))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.admissionNumber', 'ADM-1001')
            ->assertJsonPath('data.1.admissionNumber', 'ADM-2002')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_owners_can_paginate_students_for_their_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        Student::factory()->create(['school_id' => $school->id, 'admission_number' => 'ADM-1001']);
        Student::factory()->create(['school_id' => $school->id, 'admission_number' => 'ADM-1002']);
        Student::factory()->create(['school_id' => $school->id, 'admission_number' => 'ADM-1003']);

        $this->withToken($token)
            ->getJson(route('api.schools.students.index', [
                'school' => $school,
                'page' => 2,
                'perPage' => 2,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.admissionNumber', 'ADM-1003')
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.lastPage', 2)
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_owners_can_search_students_by_name_or_admission_number(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();

        $byFirstName = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-1001',
            'first_name' => 'Akosua',
            'middle_name' => null,
            'last_name' => 'Mensah',
        ]);
        $byMiddleName = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-1002',
            'first_name' => 'John',
            'middle_name' => 'Kwame',
            'last_name' => 'Boateng',
        ]);
        $byAdmissionNumber = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-SEARCH-9',
            'first_name' => 'Yaw',
            'middle_name' => null,
            'last_name' => 'Asante',
        ]);
        Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-9999',
            'first_name' => 'Other',
            'middle_name' => null,
            'last_name' => 'Student',
        ]);

        $this->withToken($token)
            ->getJson(route('api.schools.students.index', [
                'school' => $school,
                'searchTerm' => 'akosua',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $byFirstName->id)
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->getJson(route('api.schools.students.index', [
                'school' => $school,
                'searchTerm' => 'kwame',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $byMiddleName->id);

        $this->withToken($token)
            ->getJson(route('api.schools.students.index', [
                'school' => $school,
                'searchTerm' => 'SEARCH-9',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $byAdmissionNumber->id);
    }

    public function test_owners_can_show_a_student(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        $academicYear = AcademicYear::factory()->current()->create([
            'school_id' => $school->id,
            'name' => '2025/2026',
        ]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $schoolClass->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'ADM-2026-001',
        ]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->withToken($token)
            ->getJson(route('api.schools.students.show', [$school, $student]))
            ->assertOk()
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonPath('data.admissionNumber', 'ADM-2026-001')
            ->assertJsonPath('data.firstName', 'John')
            ->assertJsonPath('data.lastName', 'Doe')
            ->assertJsonPath('data.schoolClassId', $schoolClass->id)
            ->assertJsonPath('data.activeClassEnrollment.id', $enrollment->id)
            ->assertJsonPath('data.activeClassEnrollment.schoolClass.name', 'JHS 1A')
            ->assertJsonPath('data.activeClassEnrollment.academicYear.name', '2025/2026')
            ->assertJsonMissingPath('data.schoolClass');
    }

    public function test_owners_cannot_show_students_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherStudent = Student::factory()->create();

        $this->withToken($token)
            ->getJson(route('api.schools.students.show', [$school, $otherStudent]))
            ->assertForbidden();
    }

    public function test_owners_can_update_student_information(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'John',
            'middle_name' => null,
            'last_name' => 'Doe',
            'gender' => Gender::Male,
            'date_of_birth' => '2012-04-15',
        ]);

        $this->withToken($token)
            ->putJson(route('api.schools.students.update', [$school, $student]), [
                'firstName' => 'Jane',
                'middleName' => 'Ama',
                'lastName' => 'Smith',
                'gender' => Gender::Female->value,
                'dateOfBirth' => '2011-08-20',
            ])
            ->assertOk()
            ->assertJsonPath('data.admissionNumber', 'ADM-2026-001')
            ->assertJsonPath('data.firstName', 'Jane')
            ->assertJsonPath('data.middleName', 'Ama')
            ->assertJsonPath('data.lastName', 'Smith')
            ->assertJsonPath('data.gender', Gender::Female->value)
            ->assertJsonPath('data.dateOfBirth', '2011-08-20')
            ->assertJsonMissingPath('data.schoolClass')
            ->assertJsonMissingPath('data.activeClassEnrollment');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Jane',
            'middle_name' => 'Ama',
            'last_name' => 'Smith',
            'gender' => Gender::Female->value,
        ]);
    }

    public function test_owners_cannot_update_students_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherStudent = Student::factory()->create();

        $this->withToken($token)
            ->putJson(route('api.schools.students.update', [$school, $otherStudent]), [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'gender' => Gender::Female->value,
                'dateOfBirth' => '2011-08-20',
            ])
            ->assertForbidden();
    }

    public function test_owners_can_list_subjects_a_student_is_enrolled_in(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $schoolClass->id,
        ]);
        $enrollment = StudentClassEnrollment::factory()->create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
        ]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $french = Subject::factory()->create(['school_id' => $school->id, 'name' => 'French']);
        $dropped = Subject::factory()->create(['school_id' => $school->id, 'name' => 'ICT']);

        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $mathematics->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);
        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $french->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Active,
        ]);
        StudentSubject::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $dropped->id,
            'school_class_id' => $schoolClass->id,
            'student_class_enrollment_id' => $enrollment->id,
            'status' => StudentSubjectStatus::Dropped,
        ]);

        $this->withToken($token)
            ->getJson(route('api.schools.students.subjects.index', [$school, $student]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subject.name', 'Mathematics')
            ->assertJsonPath('data.1.subject.name', 'French');
    }

    public function test_owners_cannot_list_subjects_for_students_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherStudent = Student::factory()->create();

        $this->withToken($token)
            ->getJson(route('api.schools.students.subjects.index', [$school, $otherStudent]))
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_students(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);

        $this->getJson(route('api.schools.students.index', $school))
            ->assertUnauthorized();

        $this->getJson(route('api.schools.students.show', [$school, $student]))
            ->assertUnauthorized();

        $this->getJson(route('api.schools.students.subjects.index', [$school, $student]))
            ->assertUnauthorized();

        $this->putJson(route('api.schools.students.update', [$school, $student]), [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'gender' => Gender::Female->value,
            'dateOfBirth' => '2011-08-20',
        ])->assertUnauthorized();

        $this->postJson(route('api.schools.students.store', $school), [
            'admissionNumber' => 'ADM-2026-001',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'gender' => Gender::Male->value,
            'dateOfBirth' => '2012-04-15',
            'schoolClassId' => fake()->uuid(),
        ])->assertUnauthorized();
    }

    public function test_owners_cannot_admit_a_student_without_a_current_academic_year(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);

        $this->withToken($token)
            ->postJson(route('api.schools.students.store', $school), [
                'admissionNumber' => 'ADM-2026-001',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'gender' => Gender::Male->value,
                'dateOfBirth' => '2012-04-15',
                'schoolClassId' => $schoolClass->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No current academic year is set for this school.');
    }
}
