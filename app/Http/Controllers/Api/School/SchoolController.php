<?php

namespace App\Http\Controllers\Api\School;

use App\Actions\Api\School\ListSchoolAction;
use App\Actions\Api\School\SetSchoolInSessionAction;
use App\Actions\Api\School\StoreSchoolAction;
use App\Actions\Api\School\UpdateSchoolAction;
use App\Actions\Api\School\UpdateSchoolStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\School\ListSchoolRequest;
use App\Http\Requests\Api\School\SetSchoolInSessionRequest;
use App\Http\Requests\Api\School\StoreSchoolRequest;
use App\Http\Requests\Api\School\UpdateSchoolRequest;
use App\Http\Requests\Api\School\UpdateSchoolStatusRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SchoolController extends Controller
{
    #[OA\Get(
        path: '/schools',
        summary: 'List schools for the authenticated owner',
        description: 'Returns all schools owned by the authenticated client.',
        security: [['sanctum' => []]],
        tags: ['Schools'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Schools retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Schools retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/School')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListSchoolRequest $request, ListSchoolAction $action): JsonResponse
    {
        return $action->handle($request->user());
    }

    #[OA\Post(
        path: '/schools',
        summary: 'Create a school for the authenticated owner',
        description: 'Creates a school owned by the authenticated client and initializes default mark settings. New schools start as active and not in session. motto and email are optional.',
        security: [['sanctum' => []]],
        tags: ['Schools'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'address', 'city', 'type', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ridge SHS'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 255, example: '12 Independence Ave'),
                    new OA\Property(property: 'city', type: 'string', maxLength: 255, example: 'Accra'),
                    new OA\Property(property: 'type', type: 'string', enum: ['private', 'public'], example: 'private'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000000'),
                    new OA\Property(property: 'motto', type: 'string', maxLength: 255, nullable: true, example: 'Excellence'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true, example: 'office@ridge.edu.gh'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'School created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'School created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/School'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreSchoolRequest $request, StoreSchoolAction $action): JsonResponse
    {
        return $action->handle($request, $request->user());
    }

    #[OA\Put(
        path: '/schools/{school}',
        summary: 'Update a school',
        description: 'Updates school profile fields for a school owned by the authenticated client. motto and email are optional. Use the in-session and status endpoints to change those values.',
        security: [['sanctum' => []]],
        tags: ['Schools'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'address', 'city', 'type', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ridge SHS'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 255, example: '12 Independence Ave'),
                    new OA\Property(property: 'city', type: 'string', maxLength: 255, example: 'Accra'),
                    new OA\Property(property: 'type', type: 'string', enum: ['private', 'public'], example: 'private'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000000'),
                    new OA\Property(property: 'motto', type: 'string', maxLength: 255, nullable: true, example: 'Excellence'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true, example: 'office@ridge.edu.gh'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'School updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'School updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/School'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(UpdateSchoolRequest $request, School $school, UpdateSchoolAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/in-session',
        summary: 'Set a school in session',
        description: 'Marks the given school as in session and sets inSession to false for all other schools owned by the same client.',
        security: [['sanctum' => []]],
        tags: ['Schools'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'School set in session successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'School set in session successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/School'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function setInSession(
        SetSchoolInSessionRequest $request,
        School $school,
        SetSchoolInSessionAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    #[OA\Put(
        path: '/schools/{school}/status',
        summary: 'Update school status',
        description: 'Updates the status of a school owned by the authenticated client.',
        security: [['sanctum' => []]],
        tags: ['Schools'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'School status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'School status updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/School'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateStatus(
        UpdateSchoolStatusRequest $request,
        School $school,
        UpdateSchoolStatusAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
