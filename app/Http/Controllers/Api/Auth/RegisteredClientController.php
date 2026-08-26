<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\Role;
use App\Enums\SchoolType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SignUpRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class RegisteredClientController extends Controller
{
    /**
     * Register a school-owner client and issue a Sanctum token.
     */
    public function store(SignUpRequest $request): JsonResponse
    {
        $client = Client::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => Role::Owner,
        ]);

        School::create([
            'name' => $request->string('schoolName'),
            'address' => $request->string('address'),
            'city' => $request->string('city'),
            'type' => $request->enum('type', SchoolType::class),
            'phone' => $request->string('phone'),
            'owner_id' => $client->id,
        ]);

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
