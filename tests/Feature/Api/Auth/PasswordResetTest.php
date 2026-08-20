<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Client;
use App\Notifications\ResetClientPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $client = Client::factory()->create();

        $this->postJson(route('api.auth.forgot-password'), [
            'email' => $client->email,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If that email exists, we sent a reset link.');

        Notification::assertSentTo($client, ResetClientPassword::class);
    }

    public function test_unknown_emails_do_not_reveal_whether_a_client_exists(): void
    {
        Notification::fake();

        $this->postJson(route('api.auth.forgot-password'), [
            'email' => 'missing@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If that email exists, we sent a reset link.');

        Notification::assertNothingSent();
    }

    public function test_clients_can_reset_their_password_with_a_valid_token(): void
    {
        Notification::fake();

        $client = Client::factory()->create();
        $client->createToken('api-owner', ['permit:owner']);

        $this->postJson(route('api.auth.forgot-password'), [
            'email' => $client->email,
        ]);

        Notification::assertSentTo($client, ResetClientPassword::class, function (ResetClientPassword $notification) use ($client) {
            $this->postJson(route('api.auth.reset-password'), [
                'email' => $client->email,
                'token' => $notification->token,
                'password' => 'Password1!',
            ])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Password reset successfully.');

            $this->assertTrue(Hash::check('Password1!', $client->fresh()->password));
            $this->assertDatabaseCount('personal_access_tokens', 0);

            return true;
        });
    }

    public function test_clients_cannot_reset_their_password_with_an_invalid_token(): void
    {
        $client = Client::factory()->create();

        $this->postJson(route('api.auth.reset-password'), [
            'email' => $client->email,
            'token' => 'invalid-token',
            'password' => 'Password1!',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertTrue(Hash::check('password', $client->fresh()->password));
    }

    public function test_clients_cannot_reset_their_password_with_a_weak_password(): void
    {
        Notification::fake();

        $client = Client::factory()->create();

        $this->postJson(route('api.auth.forgot-password'), [
            'email' => $client->email,
        ]);

        Notification::assertSentTo($client, ResetClientPassword::class, function (ResetClientPassword $notification) use ($client) {
            $this->postJson(route('api.auth.reset-password'), [
                'email' => $client->email,
                'token' => $notification->token,
                'password' => 'password',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);

            return true;
        });
    }

    public function test_forgot_password_requests_are_rate_limited(): void
    {
        $client = Client::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.auth.forgot-password'), [
                'email' => $client->email,
            ])->assertOk();
        }

        $this->postJson(route('api.auth.forgot-password'), [
            'email' => $client->email,
        ])->assertTooManyRequests();
    }
}
