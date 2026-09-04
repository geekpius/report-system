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
use OpenApi\Attributes as OA;

class AuthenticatedSessionController extends Controller
{
    /**
     * Authenticate a client and issue a Sanctum token.
     */
    #[OA\Post(
        path: '/auth/login',
        summary: 'Log in',
        description: 'Authenticate a client and return a Sanctum token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password1!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged in successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged in successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokenData'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
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
    #[OA\Get(
        path: '/auth/me',
        summary: 'Get authenticated client',
        description: 'Returns the currently authenticated client with their schools.',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Client retrieved successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Client'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
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
    #[OA\Post(
        path: '/auth/logout',
        summary: 'Log out',
        description: 'Revokes the current Sanctum personal access token.',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->success(message: 'Logged out successfully.');
    }
}
