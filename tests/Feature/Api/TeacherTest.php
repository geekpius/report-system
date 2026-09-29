<?php

namespace Tests\Feature\Api;

use App\Enums\ClientStatus;
use App\Enums\Role;
use App\Models\ClassSubjectTeacher;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Notifications\ResetClientPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeacherTest extends TestCase
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

    public function test_owners_can_create_a_teacher_and_send_a_password_reset_link(): void
    {
        Notification::fake();
        ['school' => $school, 'token' => $token] = $this->ownerContext();

        $response = $this->withToken($token)
            ->postJson(route('api.schools.teachers.store', $school), [
                'name' => 'Kwame Mensah',
                'email' => 'teacher@example.com',
                'staffNumber' => 'STF-1001',
                'phone' => '0240000001',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.staffNumber', 'STF-1001')
            ->assertJsonPath('data.phone', '0240000001')
            ->assertJsonPath('data.schoolId', $school->id)
            ->assertJsonPath('data.client.email', 'teacher@example.com')
            ->assertJsonPath('data.client.role', Role::Teacher->value)
            ->assertJsonPath('data.client.status', ClientStatus::Active->value);

        $this->assertDatabaseHas('clients', [
            'email' => 'teacher@example.com',
            'role' => Role::Teacher->value,
            'status' => ClientStatus::Active->value,
        ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $response->json('data.id'),
            'school_id' => $school->id,
            'staff_number' => 'STF-1001',
        ]);

        $client = Client::query()->where('email', 'teacher@example.com')->firstOrFail();
        Notification::assertSentTo($client, ResetClientPassword::class);
    }

    public function test_owners_cannot_create_a_teacher_with_a_duplicate_email_or_staff_number(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        Teacher::factory()->create([
            'school_id' => $school->id,
            'staff_number' => 'STF-1001',
            'client_id' => Client::factory()->teacher()->create([
                'email' => 'teacher@example.com',
            ])->id,
        ]);

        $this->withToken($token)
            ->postJson(route('api.schools.teachers.store', $school), [
                'name' => 'Another Teacher',
                'email' => 'teacher@example.com',
                'staffNumber' => 'STF-2002',
                'phone' => '0240000002',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->withToken($token)
            ->postJson(route('api.schools.teachers.store', $school), [
                'name' => 'Another Teacher',
                'email' => 'other@example.com',
                'staffNumber' => 'STF-1001',
                'phone' => '0240000002',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staffNumber']);
    }

    public function test_owners_can_list_teachers_for_their_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        Teacher::factory()->create([
            'school_id' => $school->id,
            'staff_number' => 'STF-2002',
        ]);
        Teacher::factory()->create([
            'school_id' => $school->id,
            'staff_number' => 'STF-1001',
        ]);
        Teacher::factory()->create(['staff_number' => 'STF-9999']);

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.index', $school))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.staffNumber', 'STF-1001')
            ->assertJsonPath('data.1.staffNumber', 'STF-2002')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.perPage', 15)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_owners_can_paginate_teachers_for_their_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        Teacher::factory()->create(['school_id' => $school->id, 'staff_number' => 'STF-1001']);
        Teacher::factory()->create(['school_id' => $school->id, 'staff_number' => 'STF-1002']);
        Teacher::factory()->create(['school_id' => $school->id, 'staff_number' => 'STF-1003']);

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.index', [
                'school' => $school,
                'page' => 2,
                'perPage' => 2,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.staffNumber', 'STF-1003')
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.lastPage', 2)
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_owners_can_show_a_teacher(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $client = Client::factory()->teacher()->create([
            'name' => 'Kwame Mensah',
            'email' => 'teacher@example.com',
        ]);
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'client_id' => $client->id,
            'staff_number' => 'STF-1001',
            'phone' => '0240000001',
        ]);

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.show', [$school, $teacher]))
            ->assertOk()
            ->assertJsonPath('data.id', $teacher->id)
            ->assertJsonPath('data.staffNumber', 'STF-1001')
            ->assertJsonPath('data.phone', '0240000001')
            ->assertJsonPath('data.client.name', 'Kwame Mensah')
            ->assertJsonPath('data.client.email', 'teacher@example.com');
    }

    public function test_owners_cannot_show_teachers_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherTeacher = Teacher::factory()->create();

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.show', [$school, $otherTeacher]))
            ->assertForbidden();
    }

    public function test_owners_can_list_subjects_taught_by_a_teacher(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $otherTeacher = Teacher::factory()->create(['school_id' => $school->id]);
        $firstClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $secondClass = SchoolClass::factory()->create(['school_id' => $school->id]);
        $mathematics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        $physics = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Physics']);

        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $firstClass->id,
            'subject_id' => $mathematics->id,
            'teacher_id' => $teacher->id,
        ]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $secondClass->id,
            'subject_id' => $mathematics->id,
            'teacher_id' => $teacher->id,
        ]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $firstClass->id,
            'subject_id' => $english->id,
            'teacher_id' => $teacher->id,
        ]);
        ClassSubjectTeacher::factory()->create([
            'school_class_id' => $firstClass->id,
            'subject_id' => $physics->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.subjects.index', [$school, $teacher]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'English')
            ->assertJsonPath('data.1.name', 'Mathematics');
    }

    public function test_owners_cannot_list_subjects_for_teachers_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherTeacher = Teacher::factory()->create();

        $this->withToken($token)
            ->getJson(route('api.schools.teachers.subjects.index', [$school, $otherTeacher]))
            ->assertForbidden();
    }

    public function test_owners_can_update_teacher_information(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $client = Client::factory()->teacher()->create(['name' => 'Old Name']);
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'client_id' => $client->id,
            'staff_number' => 'STF-1001',
            'phone' => '0240000001',
        ]);

        $this->withToken($token)
            ->putJson(route('api.schools.teachers.update', [$school, $teacher]), [
                'name' => 'Kwame Mensah',
                'phone' => '0241111111',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '0241111111')
            ->assertJsonPath('data.client.name', 'Kwame Mensah')
            ->assertJsonPath('data.staffNumber', 'STF-1001');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'staff_number' => 'STF-1001',
            'phone' => '0241111111',
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Kwame Mensah',
        ]);
    }

    public function test_owners_cannot_update_teachers_from_another_school(): void
    {
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $otherTeacher = Teacher::factory()->create();

        $this->withToken($token)
            ->putJson(route('api.schools.teachers.update', [$school, $otherTeacher]), [
                'name' => 'Kwame Mensah',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_owners_can_reset_a_teacher_password(): void
    {
        Notification::fake();
        ['school' => $school, 'token' => $token] = $this->ownerContext();
        $client = Client::factory()->teacher()->create(['email' => 'teacher@example.com']);
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'client_id' => $client->id,
        ]);

        $this->withToken($token)
            ->postJson(route('api.schools.teachers.reset-password', [$school, $teacher]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $teacher->id);

        Notification::assertSentTo($client, ResetClientPassword::class);
    }

    public function test_suspended_clients_cannot_log_in(): void
    {
        $client = Client::factory()->owner()->create([
            'email' => 'owner@example.com',
            'status' => ClientStatus::Suspended,
            'password' => 'Password1!',
        ]);

        $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'Password1!',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is suspended. Please contact support.');
    }

    public function test_guests_cannot_manage_teachers(): void
    {
        $school = School::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);

        $this->getJson(route('api.schools.teachers.index', $school))
            ->assertUnauthorized();

        $this->getJson(route('api.schools.teachers.show', [$school, $teacher]))
            ->assertUnauthorized();

        $this->getJson(route('api.schools.teachers.subjects.index', [$school, $teacher]))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.teachers.store', $school), [
            'name' => 'Kwame Mensah',
            'email' => 'teacher@example.com',
            'staffNumber' => 'STF-1001',
            'phone' => '0240000001',
        ])->assertUnauthorized();

        $this->putJson(route('api.schools.teachers.update', [$school, $teacher]), [
            'name' => 'Kwame Mensah',
            'phone' => '0241111111',
        ])->assertUnauthorized();

        $this->postJson(route('api.schools.teachers.reset-password', [$school, $teacher]))
            ->assertUnauthorized();
    }
}
