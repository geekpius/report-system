<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\Role;
use App\Enums\SchoolType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RegisteredClientController extends Controller
{
    /**
     * Register a school-owner client and issue a Sanctum token.
     */
    #[OA\Post(
        path: '/auth/sign-up',
        summary: 'Register a school owner',
        description: 'Creates an owner client and their school, then returns a Sanctum token.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'schoolName', 'address', 'city', 'type', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ama Owner'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password1!'),
                    new OA\Property(property: 'schoolName', type: 'string', maxLength: 255, example: 'Ridge SHS'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 255, example: '12 Independence Ave'),
                    new OA\Property(property: 'city', type: 'string', maxLength: 255, example: 'Accra'),
                    new OA\Property(property: 'type', type: 'string', enum: ['private', 'public'], example: 'private'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000000'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Registered successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokenData'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function store(SignUpRequest $request): JsonResponse
    {
        $client = DB::transaction(function () use ($request): Client {
            $client = Client::create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'role' => Role::Owner,
            ]);

            $school = School::create([
                'name' => $request->string('schoolName'),
                'address' => $request->string('address'),
                'city' => $request->string('city'),
                'type' => $request->enum('type', SchoolType::class),
                'phone' => $request->string('phone'),
                'owner_id' => $client->id,
            ]);

            MarkSetting::resolveForSchool($school);

            return $client;
        });

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
        ], 'Registered successfully.', 201);
    }
}
