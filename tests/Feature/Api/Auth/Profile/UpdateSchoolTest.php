<?php

namespace Tests\Feature\Api\Auth\Profile;

use App\Models\Client;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSchoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_can_update_their_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create([
            'name' => 'Ridge JHS',
            'address' => '12 Ridge Street',
            'phone' => '0240000000',
        ]);
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.schools.update', $school), [
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'phone' => '0241111111',
                'motto' => 'Learn well',
                'email' => 'office@ridge.edu.gh',
                'imageUrl' => 'https://example.com/logo.png',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $school->id)
            ->assertJsonPath('data.name', 'Ridge SHS')
            ->assertJsonPath('data.address', '14 Ridge Street')
            ->assertJsonPath('data.phone', '0241111111')
            ->assertJsonPath('data.motto', 'Learn well')
            ->assertJsonPath('data.email', 'office@ridge.edu.gh')
            ->assertJsonPath('data.imageUrl', 'https://example.com/logo.png')
            ->assertJsonPath('data.ownerId', $owner->id);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Ridge SHS',
            'owner_id' => $owner->id,
        ]);
    }

    public function test_owners_cannot_change_the_school_owner(): void
    {
        $owner = Client::factory()->owner()->create();
        $otherOwner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.schools.update', $school), [
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'phone' => '0241111111',
                'owner_id' => $otherOwner->id,
            ])
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
            ->putJson(route('api.profile.schools.update', $otherSchool), [
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_tokens_without_the_owner_ability_cannot_update_a_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.schools.update', $school), [
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_teachers_cannot_update_a_school(): void
    {
        $teacher = Client::factory()->teacher()->create();
        $school = School::factory()->create();
        $token = $teacher->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.schools.update', $school), [
                'name' => 'Ridge SHS',
                'address' => '14 Ridge Street',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_update_a_school(): void
    {
        $school = School::factory()->create();

        $this->putJson(route('api.profile.schools.update', $school), [
            'name' => 'Ridge SHS',
            'address' => '14 Ridge Street',
            'phone' => '0241111111',
        ])->assertUnauthorized();
    }

    public function test_school_update_requires_core_fields(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.schools.update', $school), [
                'motto' => 'Learn well',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address', 'phone']);
    }
}
