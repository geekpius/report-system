<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    /**
     * Authenticate a client and issue a Sanctum token.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $client = Client::query()->where('email', $request->string('email'))->first();

        if (! $client || ! Hash::check($request->string('password'), $client->password)) {
            return $this->error(__('auth.failed'), 401);
        }

        $tokenName = 'api-'.$client->role->value;
        $permissions = 'permit:'.$client->role->value;

        $token = $client->createToken(
            $tokenName,
            [$permissions],
            now()->addMinutes((int) config('sanctum.expiration', 60 * 24)),
        )->plainTextToken;

        $client->load('schools');

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'client' => ClientResource::make($client)->resolve(),
        ], 'Logged in successfully.');
    }

    /**
     * Return the authenticated client.
     */
    public function show(Request $request): JsonResponse
    {
        $client = $request->user()->load('schools');

        return $this->success(
            ClientResource::make($client),
            'Client retrieved successfully.',
        );
    }

    /**
     * Revoke the current client token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success(message: 'Logged out successfully.');
    }
}
