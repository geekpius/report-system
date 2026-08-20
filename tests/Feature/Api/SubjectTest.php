<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_a_subject_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [
                'name' => 'Mathematics',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Mathematics')
            ->assertJsonPath('data.schoolId', $school->id);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'school_id' => $school->id,
        ]);
    }

    public function test_owners_cannot_create_a_duplicate_subject_in_the_same_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        Subject::factory()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [
                'name' => 'Mathematics',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_owners_can_list_subjects_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        Subject::factory()->create(['school_id' => $school->id, 'name' => 'English']);
        Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics']);
        Subject::factory()->create(['name' => 'Other School Subject']);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.subjects.index', $school))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'English')
            ->assertJsonPath('data.1.name', 'Mathematics');
    }

    public function test_owners_cannot_manage_subjects_for_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.subjects.index', $otherSchool))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $otherSchool), [
                'name' => 'Mathematics',
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_manage_subjects(): void
    {
        $teacher = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $token = $teacher->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.subjects.index', $school))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [
                'name' => 'Mathematics',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_subjects(): void
    {
        $school = School::factory()->create();

        $this->getJson(route('api.schools.subjects.index', $school))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.subjects.store', $school), [
            'name' => 'Mathematics',
        ])->assertUnauthorized();
    }

    public function test_subject_store_requires_a_name(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
