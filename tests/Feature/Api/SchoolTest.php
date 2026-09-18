<?php

namespace Tests\Feature\Api;

use App\Enums\SchoolStatus;
use App\Enums\SchoolType;
use App\Enums\ScoringMode;
use App\Models\Client;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function schoolPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ridge SHS',
            'address' => '12 Independence Ave',
            'city' => 'Accra',
            'type' => SchoolType::Private->value,
            'phone' => '0240000000',
            'motto' => 'Excellence',
            'email' => 'office@ridge.edu.gh',
        ], $overrides);
    }

    public function test_owners_can_create_a_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.schools.store'), $this->schoolPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Ridge SHS')
            ->assertJsonPath('data.address', '12 Independence Ave')
            ->assertJsonPath('data.city', 'Accra')
            ->assertJsonPath('data.type', SchoolType::Private->value)
            ->assertJsonPath('data.phone', '0240000000')
            ->assertJsonPath('data.motto', 'Excellence')
            ->assertJsonPath('data.email', 'office@ridge.edu.gh')
            ->assertJsonPath('data.status', SchoolStatus::Active->value)
            ->assertJsonPath('data.inSession', false)
            ->assertJsonPath('data.ownerId', $owner->id);

        $schoolId = $response->json('data.id');

        $this->assertDatabaseHas('schools', [
            'id' => $schoolId,
            'name' => 'Ridge SHS',
            'owner_id' => $owner->id,
            'status' => SchoolStatus::Active->value,
            'in_session' => false,
        ]);

        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $schoolId,
            'scoring_mode' => ScoringMode::TotalScore->value,
        ]);
        $this->assertTrue(MarkSetting::query()->where('school_id', $schoolId)->exists());
    }

    public function test_school_create_requires_all_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.schools.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address', 'city', 'type', 'phone', 'motto', 'email']);
    }

    public function test_owners_can_update_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create([
            'name' => 'Ridge JHS',
            'in_session' => false,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.update', $school), $this->schoolPayload([
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'city' => 'Kumasi',
                'type' => SchoolType::Public->value,
                'phone' => '0241111111',
                'motto' => 'Learn well',
                'email' => 'office@ridge.edu.gh',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $school->id)
            ->assertJsonPath('data.name', 'Ridge SHS')
            ->assertJsonPath('data.address', '14 Ridge Street')
            ->assertJsonPath('data.city', 'Kumasi')
            ->assertJsonPath('data.type', SchoolType::Public->value)
            ->assertJsonPath('data.phone', '0241111111')
            ->assertJsonPath('data.motto', 'Learn well')
            ->assertJsonPath('data.email', 'office@ridge.edu.gh')
            ->assertJsonPath('data.inSession', false)
            ->assertJsonPath('data.ownerId', $owner->id);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Ridge SHS',
            'owner_id' => $owner->id,
            'in_session' => false,
        ]);
    }

    public function test_owners_cannot_change_the_school_owner(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherOwner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.update', $school), $this->schoolPayload([
                'owner_id' => $otherOwner->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.ownerId', $owner->id);

        $this->assertSame($owner->id, $school->fresh()->owner_id);
    }

    public function test_owners_cannot_update_another_owners_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.update', $otherSchool), $this->schoolPayload())
            ->assertForbidden();
    }

    public function test_school_update_requires_all_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.update', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address', 'city', 'type', 'phone', 'motto', 'email']);
    }

    public function test_owners_can_set_a_school_in_session_and_clear_others(): void
    {
        $owner = Client::factory()->owner()->create();
        $first = School::factory()->for($owner, 'owner')->create([
            'name' => 'First School',
            'in_session' => true,
        ]);
        $second = School::factory()->for($owner, 'owner')->create([
            'name' => 'Second School',
            'in_session' => false,
        ]);
        $otherOwnerSchool = School::factory()->create(['in_session' => true]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.in-session', $second))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $second->id)
            ->assertJsonPath('data.inSession', true);

        $this->assertTrue($second->fresh()->in_session);
        $this->assertFalse($first->fresh()->in_session);
        $this->assertTrue($otherOwnerSchool->fresh()->in_session);
    }

    public function test_owners_cannot_set_another_owners_school_in_session(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.in-session', $otherSchool))
            ->assertForbidden();
    }

    public function test_owners_can_update_school_status(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create([
            'status' => SchoolStatus::Active,
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.status', $school), [
                'status' => SchoolStatus::Archived->value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $school->id)
            ->assertJsonPath('data.status', SchoolStatus::Archived->value);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'status' => SchoolStatus::Archived->value,
        ]);
    }

    public function test_school_status_update_requires_status(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.status', $school), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_owners_cannot_update_another_owners_school_status(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherSchool = School::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.schools.status', $otherSchool), [
                'status' => SchoolStatus::Archived->value,
            ])
            ->assertForbidden();
    }

    public function test_owners_can_list_their_schools(): void
    {
        $owner = Client::factory()->owner()->create();
        School::factory()->for($owner, 'owner')->create(['name' => 'Ridge JHS']);
        School::factory()->for($owner, 'owner')->create(['name' => 'Achimota SHS']);
        School::factory()->create(['name' => 'Other School']);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Achimota SHS')
            ->assertJsonPath('data.1.name', 'Ridge JHS')
            ->assertJsonPath('data.0.status', SchoolStatus::Active->value)
            ->assertJsonPath('data.0.ownerId', $owner->id);
    }

    public function test_tokens_without_the_owner_ability_cannot_manage_schools(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.schools.index'))
            ->assertForbidden();

        $this->withToken($token)
            ->postJson(route('api.schools.store'), $this->schoolPayload())
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.update', $school), $this->schoolPayload())
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.in-session', $school))
            ->assertForbidden();

        $this->withToken($token)
            ->putJson(route('api.schools.status', $school), [
                'status' => SchoolStatus::Archived->value,
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_schools(): void
    {
        $school = School::factory()->create();

        $this->getJson(route('api.schools.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.schools.store'), $this->schoolPayload())
            ->assertUnauthorized();

        $this->putJson(route('api.schools.update', $school), $this->schoolPayload())
            ->assertUnauthorized();

        $this->putJson(route('api.schools.in-session', $school))
            ->assertUnauthorized();

        $this->putJson(route('api.schools.status', $school), [
            'status' => SchoolStatus::Archived->value,
        ])->assertUnauthorized();
    }
}
