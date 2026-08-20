<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\Role;
use App\Models\Client;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_can_sign_up_via_the_api(): void
    {
        $response = $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'address' => '12 Ridge Street',
            'phone' => '0240000000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('client.email', 'ama@example.com')
            ->assertJsonPath('client.role', Role::Owner->value)
            ->assertJsonMissingPath('client.password')
            ->assertJsonPath('client.schools.0.phone', '0240000000');

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('clients', [
            'email' => 'ama@example.com',
            'role' => Role::Owner->value,
        ]);

        $client = Client::query()->where('email', 'ama@example.com')->first();

        $this->assertTrue(Hash::check('password', $client->password));
        $this->assertNotSame('password', $client->getRawOriginal('password'));
        $this->assertGuest();

        $this->withToken($response->json('token'))
            ->getJson(route('api.auth.me'))
            ->assertOk()
            ->assertJsonPath('email', 'ama@example.com');
    }

    public function test_clients_cannot_sign_up_with_a_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'ama@example.com']);

        $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'address' => '12 Ridge Street',
            'phone' => '0240000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_sign_up_ignores_a_submitted_role_and_creates_an_owner(): void
    {
        $this->postJson(route('api.auth.sign-up'), [
            'name' => 'Ama Owner',
            'email' => 'ama@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'address' => '12 Ridge Street',
            'phone' => '0240000000',
            'role' => Role::Teacher->value,
        ])->assertCreated()
            ->assertJsonPath('client.role', Role::Owner->value);

        $this->assertDatabaseHas('clients', [
            'email' => 'ama@example.com',
            'role' => Role::Owner->value,
        ]);
    }

    public function test_clients_can_authenticate_via_the_api(): void
    {
        $client = Client::factory()->owner()->create();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('client.id', $client->id)
            ->assertJsonPath('client.role', Role::Owner->value)
            ->assertJsonMissingPath('client.password');

        $this->assertNotEmpty($response->json('token'));
        $this->assertGuest();
    }

    public function test_clients_cannot_authenticate_with_invalid_password(): void
    {
        $client = Client::factory()->create();

        $this->postJson(route('api.auth.login'), [
            'email' => $client->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', __('auth.failed'));

        $this->assertGuest();
    }

    public function test_authenticated_clients_can_fetch_themselves(): void
    {
        $client = Client::factory()->teacher()->create();
        $token = $client->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.auth.me'))
            ->assertOk()
            ->assertJsonPath('id', $client->id)
            ->assertJsonPath('role', Role::Teacher->value)
            ->assertJsonMissingPath('password');
    }

    public function test_clients_can_logout_and_revoke_the_current_token(): void
    {
        $client = Client::factory()->create();
        $token = $client->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('api.auth.logout'))
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson(route('api.auth.me'))
            ->assertUnauthorized();
    }

    public function test_web_admin_sessions_cannot_access_the_client_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.auth.me'))
            ->assertUnauthorized();
    }

    public function test_owners_can_own_a_school(): void
    {
        $owner = Client::factory()->owner()->create();
        $school = School::factory()->for($owner, 'owner')->create([
            'name' => 'Ridge JHS',
            'phone' => '0240000000',
        ]);

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
            ->assertJsonPath('message', __('auth.throttle', [
                'seconds' => $retryAfter,
                'minutes' => (int) ceil($retryAfter / 60),
            ]));
    }
}
