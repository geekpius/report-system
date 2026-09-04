<?php

namespace App\Http\Controllers\Api\Aggregate;

use App\Actions\Api\Aggregate\ListAggregateAction;
use App\Actions\Api\Aggregate\StoreAggregateAction;
use App\Actions\Api\Aggregate\UpdateAggregateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Aggregate\ListAggregateRequest;
use App\Http\Requests\Api\Aggregate\StoreAggregateRequest;
use App\Http\Requests\Api\Aggregate\UpdateAggregateRequest;
use App\Models\Aggregate;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AggregateController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/aggregates',
        summary: 'List grade aggregates',
        security: [['sanctum' => []]],
        tags: ['Aggregates'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregates retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Aggregates retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Aggregate')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListAggregateRequest $request,
        School $school,
        ListAggregateAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/aggregates',
        summary: 'Create a grade aggregate',
        security: [['sanctum' => []]],
        tags: ['Aggregates'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['minScore', 'maxScore', 'grade', 'remarks'],
                properties: [
                    new OA\Property(property: 'minScore', type: 'integer', minimum: 0, maximum: 100, example: 80),
                    new OA\Property(property: 'maxScore', type: 'integer', minimum: 0, maximum: 100, example: 100),
                    new OA\Property(property: 'grade', type: 'string', maxLength: 10, example: 'A'),
                    new OA\Property(property: 'remarks', type: 'string', maxLength: 255, example: 'Excellent'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Aggregate created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Aggregate created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Aggregate'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreAggregateRequest $request,
        School $school,
        StoreAggregateAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/aggregates/{aggregate}',
        summary: 'Update a grade aggregate',
        security: [['sanctum' => []]],
        tags: ['Aggregates'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'aggregate', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'minScore', type: 'integer', minimum: 0, maximum: 100, example: 70),
                    new OA\Property(property: 'maxScore', type: 'integer', minimum: 0, maximum: 100, example: 79),
                    new OA\Property(property: 'grade', type: 'string', maxLength: 10, example: 'B'),
                    new OA\Property(property: 'remarks', type: 'string', maxLength: 255, example: 'Very Good'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregate updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Aggregate updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Aggregate'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(
        UpdateAggregateRequest $request,
        School $school,
        Aggregate $aggregate,
        UpdateAggregateAction $action,
    ): JsonResponse {
        return $action->handle($request, $aggregate);
    }
}
