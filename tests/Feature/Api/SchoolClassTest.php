<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_a_class_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $teacher = Teacher::factory()->create(['school_id' => $school->id]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $school), [
                'name' => 'JHS 1A',
                'alias' => 'Form 1',
                'class_teacher_id' => $teacher->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'JHS 1A')
            ->assertJsonPath('data.alias', 'Form 1')
            ->assertJsonPath('data.schoolId', $school->id)
            ->assertJsonPath('data.classTeacherId', $teacher->id);

        $this->assertDatabaseHas('school_classes', [
            'name' => 'JHS 1A',
            'alias' => 'Form 1',
            'school_id' => $school->id,
            'class_teacher_id' => $teacher->id,
        ]);
    }

    public function test_owners_cannot_assign_a_teacher_from_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherTeacher = Teacher::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $school), [
                'name' => 'JHS 1A',
                'class_teacher_id' => $otherTeacher->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['class_teacher_id']);
    }

    public function test_owners_can_list_classes_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1A']);
        SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'JHS 1B']);
        SchoolClass::factory()->create(['name' => 'Other School Class']);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'JHS 1A')
            ->assertJsonPath('data.1.name', 'JHS 1B');
    }

    public function test_owners_cannot_manage_classes_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $otherSchool), [
                'name' => 'JHS 1A',
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_manage_school_classes(): void
    {
        $teacher = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $token = $teacher->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.classes.index', $school))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $school), [
                'name' => 'JHS 1A',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_school_classes(): void
    {
        $school = School::factory()->create();

        $this->getJson(route('api.schools.classes.index', $school))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.classes.store', $school), [
            'name' => 'JHS 1A',
        ])->assertUnauthorized();
    }

    public function test_class_store_requires_a_name(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $school), [
                'alias' => 'Form 1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
