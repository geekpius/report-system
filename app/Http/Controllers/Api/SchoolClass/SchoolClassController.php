<?php

namespace App\Http\Controllers\Api\SchoolClass;

use App\Actions\Api\SchoolClass\ListSchoolClassAction;
use App\Actions\Api\SchoolClass\StoreSchoolClassAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SchoolClass\ListSchoolClassRequest;
use App\Http\Requests\Api\SchoolClass\StoreSchoolClassRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SchoolClassController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/classes',
        summary: 'List school classes',
        security: [['sanctum' => []]],
        tags: ['School Classes'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Classes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Classes retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SchoolClass')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListSchoolClassRequest $request, School $school, ListSchoolClassAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/classes',
        summary: 'Create a school class',
        security: [['sanctum' => []]],
        tags: ['School Classes'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'JHS 1A'),
                    new OA\Property(property: 'alias', type: 'string', maxLength: 255, nullable: true, example: 'Form 1'),
                    new OA\Property(property: 'classTeacherId', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Class created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SchoolClass'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreSchoolClassRequest $request, School $school, StoreSchoolClassAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }
}
