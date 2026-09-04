<?php

namespace App\Http\Controllers\Api\Subject;

use App\Actions\Api\Subject\ListSubjectAction;
use App\Actions\Api\Subject\StoreSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subject\ListSubjectRequest;
use App\Http\Requests\Api\Subject\StoreSubjectRequest;
use App\Models\School;
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
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mathematics'),
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
}
