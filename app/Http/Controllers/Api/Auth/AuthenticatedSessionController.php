<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $tokenName = 'api-'.$client->role->value;
        $permissions = 'permit:'.$client->role->value;

        $token = $client->createToken(
            $tokenName,
            [$permissions],
            now()->addMinutes((int) config('sanctum.expiration', 60 * 24)),
        )->plainTextToken;

        $client->load('schools');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'client' => ClientResource::make($client)->resolve(),
        ]);
    }

    /**
     * Return the authenticated client.
     */
    public function show(Request $request): JsonResponse
    {
        $client = $request->user()->load('schools');

        return response()->json(ClientResource::make($client)->resolve());
    }

    /**
     * Revoke the current client token.
     */
    public function destroy(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
