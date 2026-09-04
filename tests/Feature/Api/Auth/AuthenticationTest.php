<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\Role;
use App\Enums\SchoolType;
use App\Enums\ScoringMode;
use App\Models\Client;
use App\Models\MarkSetting;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_can_sign_up_via_the_api(): void
    {
        $response = $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'Password1!',
            'schoolName' => 'Ridge JHS',
            'address' => '12 Ridge Street',
            'city' => 'Accra',
            'type' => SchoolType::Private->value,
            'phone' => '0240000000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.client.email', 'ama@example.com')
            ->assertJsonPath('data.client.role', Role::Owner->value)
            ->assertJsonMissingPath('data.client.password')
            ->assertJsonPath('data.client.schools.0.phone', '0240000000')
            ->assertJsonPath('data.client.schools.0.city', 'Accra')
            ->assertJsonPath('data.client.schools.0.type', SchoolType::Private->value);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('clients', [
            'email' => 'ama@example.com',
            'role' => Role::Owner->value,
        ]);

        $client = Client::query()->where('email', 'ama@example.com')->first();

        $this->assertTrue(Hash::check('Password1!', $client->password));
        $this->assertNotSame('Password1!', $client->getRawOriginal('password'));
        $this->assertGuest();

        $school = School::query()->where('owner_id', $client->id)->first();
        $this->assertNotNull($school);
        $this->assertDatabaseHas('mark_settings', [
            'school_id' => $school->id,
            'scoring_mode' => ScoringMode::TotalScore->value,
        ]);
        $this->assertTrue(MarkSetting::query()->where('school_id', $school->id)->exists());

        $this->withToken($response->json('data.token'))
            ->getJson(route('api.me'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'ama@example.com');
    }

    public function test_clients_cannot_sign_up_with_a_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'ama@example.com']);

        $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'Password1!',
            'schoolName' => 'Ridge JHS',
            'address' => '12 Ridge Street',
            'city' => 'Accra',
            'type' => SchoolType::Private->value,
            'phone' => '0240000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_sign_up_ignores_a_submitted_role_and_creates_an_owner(): void
    {
        $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'Password1!',
            'schoolName' => 'Ridge JHS',
            'address' => '12 Ridge Street',
            'city' => 'Accra',
            'type' => SchoolType::Private->value,
            'phone' => '0240000000',
            'role' => Role::Teacher->value,
        ])->assertCreated()
            ->assertJsonPath('data.client.role', Role::Owner->value);

        $this->assertDatabaseHas('clients', [
            'email' => 'ama@example.com',
            'role' => Role::Owner->value,
        ]);
    }

    public function test_clients_cannot_sign_up_with_a_weak_password(): void
    {
        $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'password',
            'schoolName' => 'Ridge JHS',
            'address' => '12 Ridge Street',
            'city' => 'Accra',
            'type' => SchoolType::Private->value,
            'phone' => '0240000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_clients_can_authenticate_via_the_api(): void
    {
        $client = Client::factory()->owner()->create();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.client.role', Role::Owner->value)
            ->assertJsonMissingPath('data.client.password');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertGuest();
    }

    public function test_clients_cannot_authenticate_with_invalid_password(): void
    {
        $client = Client::factory()->create();

        $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('auth.failed'));

        $this->assertGuest();
    }

    public function test_authenticated_clients_can_fetch_themselves(): void
    {
        $client = Client::factory()->teacher()->create();
        $token = $client->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.me'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.role', Role::Teacher->value)
            ->assertJsonMissingPath('data.password');
    }

    public function test_clients_can_logout_and_revoke_the_current_token(): void
    {
        $client = Client::factory()->create();
        $token = $client->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.logout'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson(route('api.me'))
            ->assertUnauthorized();
    }

    public function test_web_admin_sessions_cannot_access_the_client_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.me'))
            ->assertUnauthorized();
    }

    public function test_owners_can_own_a_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create([
            'name' => 'Ridge JHS',
            'phone' => '0240000000',
        ]);

        $this->assertTrue(Str::isUuid($owner->id));
        $this->assertTrue(Str::isUuid($school->id));
        $this->assertSame($owner->id, $school->owner_id);
        $this->assertTrue($school->owner->is($owner));
        $this->assertTrue($owner->schools->contains($school));
    }

    public function test_client_logins_are_rate_limited(): void
    {
        $client = Client::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.auth.login'), [
                'email' => $client->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'wrong-password',
        ]);

        $retryAfter = (int) $response->headers->get('Retry-After');

        $response->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('auth.throttle', [
                'seconds' => $retryAfter,
                'minutes' => (int) ceil($retryAfter / 60),
            ]));
    }
}
