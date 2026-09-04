<?php

namespace App\Http\Controllers\Api\Mark;

use App\Actions\Api\Mark\ListMarkAction;
use App\Actions\Api\Mark\StoreMarkAction;
use App\Actions\Api\Mark\UpdateMarkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mark\ListMarkRequest;
use App\Http\Requests\Api\Mark\StoreMarkRequest;
use App\Http\Requests\Api\Mark\UpdateMarkRequest;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MarkController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/marks',
        summary: 'List marks',
        security: [['sanctum' => []]],
        tags: ['Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'studentId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClassId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'termId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'subjectId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'academicYearId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Marks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Marks retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Mark')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function index(
        ListMarkRequest $request,
        School $school,
        ListMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Post(
        path: '/schools/{school}/marks',
        summary: 'Create a mark',
        security: [['sanctum' => []]],
        tags: ['Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'studentId',
                    'subjectId',
                    'schoolClassId',
                    'studentClassEnrollmentId',
                    'academicYearId',
                    'termId',
                    'classScore',
                    'homeAssignmentScore',
                    'projectScore',
                    'classTestScore',
                    'examScore',
                ],
                properties: [
                    new OA\Property(property: 'studentId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'studentClassEnrollmentId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'classScore', type: 'number', minimum: 0, maximum: 15, example: 10),
                    new OA\Property(property: 'homeAssignmentScore', type: 'number', minimum: 0, maximum: 15, example: 12),
                    new OA\Property(property: 'projectScore', type: 'number', minimum: 0, maximum: 15, example: 14),
                    new OA\Property(property: 'classTestScore', type: 'number', minimum: 0, maximum: 15, example: 13),
                    new OA\Property(property: 'examScore', type: 'number', minimum: 0, maximum: 100, example: 70),
                    new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Mark created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Mark created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Mark'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreMarkRequest $request,
        School $school,
        StoreMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/marks/{mark}',
        summary: 'Update a mark',
        security: [['sanctum' => []]],
        tags: ['Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'mark', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'classScore', type: 'number', minimum: 0, maximum: 15),
                    new OA\Property(property: 'homeAssignmentScore', type: 'number', minimum: 0, maximum: 15),
                    new OA\Property(property: 'projectScore', type: 'number', minimum: 0, maximum: 15),
                    new OA\Property(property: 'classTestScore', type: 'number', minimum: 0, maximum: 15),
                    new OA\Property(property: 'examScore', type: 'number', minimum: 0, maximum: 100),
                    new OA\Property(property: 'teacherId', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mark updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Mark updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Mark'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(
        UpdateMarkRequest $request,
        School $school,
        Mark $mark,
        UpdateMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $mark);
    }
}
