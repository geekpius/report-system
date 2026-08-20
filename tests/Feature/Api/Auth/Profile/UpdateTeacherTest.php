<?php

namespace Tests\Feature\Api\Auth\Profile;

use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_teachers_can_update_their_profile(): void
    {
        $client = Client::factory()->teacher()->create();
        $teacher = Teacher::factory()->create([
            'client_id' => $client->id,
            'staff_number' => 'STF-1001',
            'phone' => '0240000001',
        ]);
        $token = $client->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.teachers.update', $teacher), [
                'staffNumber' => 'STF-2002',
                'phone' => '0241111111',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $teacher->id)
            ->assertJsonPath('data.staffNumber', 'STF-2002')
            ->assertJsonPath('data.phone', '0241111111')
            ->assertJsonPath('data.clientId', $client->id);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'staff_number' => 'STF-2002',
            'client_id' => $client->id,
            'school_id' => $teacher->school_id,
        ]);
    }

    public function test_teachers_cannot_change_client_or_school(): void
    {
        $client = Client::factory()->teacher()->create();
        $otherClient = Client::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['client_id' => $client->id]);
        $otherSchool = School::factory()->create();
        $token = $client->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.teachers.update', $teacher), [
                'staffNumber' => 'STF-2002',
                'phone' => '0241111111',
                'client_id' => $otherClient->id,
                'school_id' => $otherSchool->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.clientId', $client->id)
            ->assertJsonPath('data.schoolId', $teacher->school_id);
    }

    public function test_teachers_cannot_update_another_teachers_profile(): void
    {
        $client = Client::factory()->teacher()->create();
        $otherTeacher = Teacher::factory()->create();
        $token = $client->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.teachers.update', $otherTeacher), [
                'staffNumber' => 'STF-2002',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_owners_cannot_update_a_teacher_profile(): void
    {
        $owner = Client::factory()->owner()->create();
        $teacher = Teacher::factory()->create();
        $token = $owner->createToken('api-owner', ['permit:owner'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.teachers.update', $teacher), [
                'staffNumber' => 'STF-2002',
                'phone' => '0241111111',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_update_a_teacher_profile(): void
    {
        $teacher = Teacher::factory()->create();

        $this->putJson(route('api.profile.teachers.update', $teacher), [
            'staffNumber' => 'STF-2002',
            'phone' => '0241111111',
        ])->assertUnauthorized();
    }

    public function test_teacher_update_requires_core_fields(): void
    {
        $client = Client::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['client_id' => $client->id]);
        $token = $client->createToken('api-teacher', ['permit:teacher'])->plainTextToken;

        $this->withToken($token)
            ->putJson(route('api.profile.teachers.update', $teacher), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['staffNumber', 'phone']);
    }
}
