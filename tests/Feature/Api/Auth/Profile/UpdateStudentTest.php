<?php

namespace Tests\Feature\Api\Auth\Profile;

use App\Enums\Gender;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_update_their_profile(): void
    {
        $client = Client::factory()->student()->create();
        $school = School::factory()->create();
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'client_id' => $client->id,
            'school_id' => $school->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'gender' => Gender::Female,
            'admission_number' => 'ADM-1001',
            'date_of_birth' => '2012-04-15',
        ]);
        $token = $client->createToken('api-student', ['permit:student'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $student), [
                'firstName' => 'Akosua',
                'lastName' => 'Boateng',
                'gender' => Gender::Female->value,
                'admissionNumber' => 'ADM-2002',
                'dateOfBirth' => '2011-06-20',
                'schoolClassId' => $class->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $student->id)
            ->assertJsonPath('data.firstName', 'Akosua')
            ->assertJsonPath('data.lastName', 'Boateng')
            ->assertJsonPath('data.gender', Gender::Female->value)
            ->assertJsonPath('data.admissionNumber', 'ADM-2002')
            ->assertJsonPath('data.dateOfBirth', '2011-06-20')
            ->assertJsonPath('data.schoolClassId', $class->id)
            ->assertJsonPath('data.clientId', $client->id);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Akosua',
            'admission_number' => 'ADM-2002',
            'client_id' => $client->id,
            'school_id' => $school->id,
        ]);
    }

    public function test_students_cannot_change_client_or_school(): void
    {
        $client = Client::factory()->student()->create();
        $otherClient = Client::factory()->student()->create();
        $student = Student::factory()->create(['client_id' => $client->id]);
        $otherSchool = School::factory()->create();
        $token = $client->createToken('api-student', ['permit:student'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $student), [
                'firstName' => 'Akosua',
                'lastName' => 'Boateng',
                'gender' => Gender::Female->value,
                'admissionNumber' => 'ADM-2002',
                'dateOfBirth' => '2011-06-20',
                'client_id' => $otherClient->id,
                'school_id' => $otherSchool->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.clientId', $client->id)
            ->assertJsonPath('data.schoolId', $student->school_id);
    }

    public function test_students_cannot_join_a_class_from_another_school(): void
    {
        $client = Client::factory()->student()->create();
        $student = Student::factory()->create(['client_id' => $client->id]);
        $otherClass = SchoolClass::factory()->create();
        $token = $client->createToken('api-student', ['permit:student'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $student), [
                'firstName' => 'Akosua',
                'lastName' => 'Boateng',
                'gender' => Gender::Female->value,
                'admissionNumber' => 'ADM-2002',
                'dateOfBirth' => '2011-06-20',
                'schoolClassId' => $otherClass->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['schoolClassId']);
    }

    public function test_students_cannot_update_another_students_profile(): void
    {
        $client = Client::factory()->student()->create();
        $otherStudent = Student::factory()->withClient()->create();
        $token = $client->createToken('api-student', ['permit:student'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $otherStudent), [
                'firstName' => 'Akosua',
                'lastName' => 'Boateng',
                'gender' => Gender::Female->value,
                'admissionNumber' => 'ADM-2002',
                'dateOfBirth' => '2011-06-20',
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_update_a_student_profile(): void
    {
        $teacher = Client::factory()->teacher()->create();
        $student = Student::factory()->withClient()->create();
        $token = $teacher->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $student), [
                'firstName' => 'Akosua',
                'lastName' => 'Boateng',
                'gender' => Gender::Female->value,
                'admissionNumber' => 'ADM-2002',
                'dateOfBirth' => '2011-06-20',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_update_a_student_profile(): void
    {
        $student = Student::factory()->withClient()->create();

        $this->putJson(route('api.profile.students.update', $student), [
            'firstName' => 'Akosua',
            'lastName' => 'Boateng',
            'gender' => Gender::Female->value,
            'admissionNumber' => 'ADM-2002',
            'dateOfBirth' => '2011-06-20',
        ])->assertUnauthorized();
    }

    public function test_student_update_requires_core_fields(): void
    {
        $client = Client::factory()->student()->create();
        $student = Student::factory()->create(['client_id' => $client->id]);
        $token = $client->createToken('api-student', ['permit:student'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.students.update', $student), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firstName', 'lastName', 'gender', 'admissionNumber', 'dateOfBirth']);
    }
}
