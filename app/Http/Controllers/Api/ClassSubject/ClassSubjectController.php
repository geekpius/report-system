<?php

namespace App\Http\Controllers\Api\ClassSubject;

use App\Actions\Api\ClassSubject\ListClassSubjectAction;
use App\Actions\Api\ClassSubject\StoreClassSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClassSubject\ListClassSubjectRequest;
use App\Http\Requests\Api\ClassSubject\StoreClassSubjectRequest;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ClassSubjectController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/classes/{schoolClass}/subjects',
        summary: 'List class subjects',
        security: [['sanctum' => []]],
        tags: ['Class Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClass', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Class subjects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class subjects retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClassSubject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListClassSubjectRequest $request,
        School $school,
        SchoolClass $schoolClass,
        ListClassSubjectAction $action,
    ): JsonResponse {
        return $action->handle($schoolClass);
    }

    #[OA\Post(
        path: '/schools/{school}/classes/{schoolClass}/subjects',
        summary: 'Add subjects to a class menu',
        security: [['sanctum' => []]],
        tags: ['Class Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClass', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subjects'],
                properties: [
                    new OA\Property(
                        property: 'subjects',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            required: ['subjectId'],
                            properties: [
                                new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'isMandatory', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Class subjects created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class subjects created successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClassSubject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreClassSubjectRequest $request,
        School $school,
        SchoolClass $schoolClass,
        StoreClassSubjectAction $action,
    ): JsonResponse {
        return $action->handle($request, $schoolClass);
    }
}
