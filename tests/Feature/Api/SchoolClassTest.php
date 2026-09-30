<?php

namespace Tests\Feature\Api;

use App\Enums\SchoolClassStatus;
use App\Models\Client;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_create_a_class_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.classes.store', $school), [
                'name' => 'JHS 1A',
                'alias' => 'Form 1',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'JHS 1A')
            ->assertJsonPath('data.alias', 'Form 1')
            ->assertJsonPath('data.schoolId', $school->id)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('school_classes', [
            'name' => 'JHS 1A',
            'alias' => 'Form 1',
            'school_id' => $school->id,
            'status' => 'active',
        ]);
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

    public function test_owners_can_update_a_class_for_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'name' => 'JHS 1A',
            'alias' => 'Form 1',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.classes.update', [$school, $schoolClass]), [
                'name' => 'JHS 1B',
                'alias' => 'Form 1B',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'JHS 1B')
            ->assertJsonPath('data.alias', 'Form 1B')
            ->assertJsonPath('data.status', SchoolClassStatus::Active->value);

        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClass->id,
            'name' => 'JHS 1B',
            'alias' => 'Form 1B',
            'status' => SchoolClassStatus::Active->value,
        ]);
    }

    public function test_owners_can_update_a_class_status(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'status' => SchoolClassStatus::Active,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.classes.status', [$school, $schoolClass]), [
                'status' => SchoolClassStatus::Inactive->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Class is now inactive.')
            ->assertJsonPath('data.status', SchoolClassStatus::Inactive->value);

        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClass->id,
            'status' => SchoolClassStatus::Inactive->value,
        ]);
    }

    public function test_owners_cannot_update_classes_from_another_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $otherClass = SchoolClass::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.classes.update', [$school, $otherClass]), [
                'name' => 'JHS 1B',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.classes.status', [$school, $otherClass]), [
                'status' => SchoolClassStatus::Inactive->value,
            ])
            ->assertForbidden();
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

        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id]);

        $this->putJson(route('api.schools.classes.update', [$school, $schoolClass]), [
            'name' => 'JHS 1B',
        ])->assertUnauthorized();

        $this->putJson(route('api.schools.classes.status', [$school, $schoolClass]), [
            'status' => SchoolClassStatus::Inactive->value,
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
