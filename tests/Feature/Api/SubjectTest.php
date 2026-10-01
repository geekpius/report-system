<?php

namespace Tests\Feature\Api;

use App\Enums\SubjectStatus;
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
                'code' => 'math',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Mathematics')
            ->assertJsonPath('data.code', 'MATH')
            ->assertJsonPath('data.status', SubjectStatus::Active->value)
            ->assertJsonPath('data.schoolId', $school->id);

        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'status' => SubjectStatus::Active->value,
            'school_id' => $school->id,
        ]);
    }

    public function test_owners_can_update_a_subject(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $subject = Subject::factory()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.subjects.update', [$school, $subject]), [
                'name' => 'Further Mathematics',
                'code' => 'fmath',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Further Mathematics')
            ->assertJsonPath('data.code', 'FMATH')
            ->assertJsonPath('data.status', SubjectStatus::Active->value);

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'Further Mathematics',
            'code' => 'FMATH',
            'status' => SubjectStatus::Active->value,
        ]);
    }

    public function test_owners_can_update_a_subject_status(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $subject = Subject::factory()->create([
            'school_id' => $school->id,
            'status' => SubjectStatus::Active,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.subjects.status', [$school, $subject]), [
                'status' => SubjectStatus::Inactive->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Subject is now inactive.')
            ->assertJsonPath('data.status', SubjectStatus::Inactive->value);

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'status' => SubjectStatus::Inactive->value,
        ]);
    }

    public function test_owners_cannot_update_subjects_from_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherSubject = Subject::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.subjects.update', [$school, $otherSubject]), [
                'name' => 'Mathematics',
                'code' => 'MATH',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.subjects.status', [$school, $otherSubject]), [
                'status' => SubjectStatus::Inactive->value,
            ])
            ->assertForbidden();
    }

    public function test_owners_cannot_create_a_duplicate_subject_in_the_same_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        Subject::factory()->create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [
                'name' => 'Mathematics',
                'code' => 'MATH-2',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [
                'name' => 'Further Mathematics',
                'code' => 'MATH',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_owners_can_list_subjects_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        Subject::factory()->create(['school_id' => $school->id, 'name' => 'English', 'code' => 'ENG']);
        Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
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
                'code' => 'MATH',
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
                'code' => 'MATH',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_subjects(): void
    {
        $school = School::factory()->create();
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        $this->getJson(route('api.schools.subjects.index', $school))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.subjects.store', $school), [
            'name' => 'Mathematics',
            'code' => 'MATH',
        ])->assertUnauthorized();

        $this->putJson(route('api.schools.subjects.update', [$school, $subject]), [
            'name' => 'Mathematics',
            'code' => 'MATH',
        ])->assertUnauthorized();

        $this->putJson(route('api.schools.subjects.status', [$school, $subject]), [
            'status' => SubjectStatus::Inactive->value,
        ])->assertUnauthorized();
    }

    public function test_subject_store_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.subjects.store', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);
    }
}
