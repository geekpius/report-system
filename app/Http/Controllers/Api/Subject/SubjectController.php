<?php

namespace App\Http\Controllers\Api\Subject;

use App\Actions\Api\Subject\ListSubjectAction;
use App\Actions\Api\Subject\StoreSubjectAction;
use App\Actions\Api\Subject\UpdateSubjectAction;
use App\Actions\Api\Subject\UpdateSubjectStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subject\ListSubjectRequest;
use App\Http\Requests\Api\Subject\StoreSubjectRequest;
use App\Http\Requests\Api\Subject\UpdateSubjectRequest;
use App\Http\Requests\Api\Subject\UpdateSubjectStatusRequest;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SubjectController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/subjects',
        summary: 'List subjects',
        security: [['sanctum' => []]],
        tags: ['Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subjects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subjects retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListSubjectRequest $request, School $school, ListSubjectAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/subjects',
        summary: 'Create a subject',
        security: [['sanctum' => []]],
        tags: ['Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mathematics'),
                    new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'MATH'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Subject created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subject created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Subject'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreSubjectRequest $request, School $school, StoreSubjectAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/subjects/{subject}',
        summary: 'Update a subject',
        description: 'Updates subject name and code. Use the status endpoint to change status.',
        security: [['sanctum' => []]],
        tags: ['Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'subject', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Further Mathematics'),
                    new OA\Property(property: 'code', type: 'string', maxLength: 50, example: 'FMATH'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subject updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subject updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Subject'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(
        UpdateSubjectRequest $request,
        School $school,
        Subject $subject,
        UpdateSubjectAction $action,
    ): JsonResponse {
        return $action->handle($request, $subject);
    }

    #[OA\Put(
        path: '/schools/{school}/subjects/{subject}/status',
        summary: 'Update subject status',
        description: 'Updates the status of a subject belonging to a school owned by the authenticated owner.',
        security: [['sanctum' => []]],
        tags: ['Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'subject', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'inactive'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subject status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Subject is now inactive.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Subject'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateStatus(
        UpdateSubjectStatusRequest $request,
        School $school,
        Subject $subject,
        UpdateSubjectStatusAction $action,
    ): JsonResponse {
        return $action->handle($request, $subject);
    }
}
