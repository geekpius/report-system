<?php

namespace App\Http\Controllers\Api\Term;

use App\Actions\Api\Term\ListTermAction;
use App\Actions\Api\Term\StoreTermAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Term\ListTermRequest;
use App\Http\Requests\Api\Term\StoreTermRequest;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class TermController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/academic-years/{academicYear}/terms',
        summary: 'List terms',
        security: [['sanctum' => []]],
        tags: ['Terms'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'academicYear', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Terms retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Terms retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Term')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListTermRequest $request,
        School $school,
        AcademicYear $academicYear,
        ListTermAction $action,
    ): JsonResponse {
        return $action->handle($academicYear);
    }

    #[OA\Post(
        path: '/schools/{school}/academic-years/{academicYear}/terms',
        summary: 'Create a term',
        security: [['sanctum' => []]],
        tags: ['Terms'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'academicYear', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'number', 'startsOn', 'endsOn'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Term 1'),
                    new OA\Property(property: 'number', type: 'integer', minimum: 1, maximum: 12, example: 1),
                    new OA\Property(property: 'startsOn', type: 'string', format: 'date', example: '2025-09-01'),
                    new OA\Property(property: 'endsOn', type: 'string', format: 'date', example: '2025-12-15'),
                    new OA\Property(property: 'isCurrent', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Term created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Term created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Term'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreTermRequest $request,
        School $school,
        AcademicYear $academicYear,
        StoreTermAction $action,
    ): JsonResponse {
        return $action->handle($request, $academicYear);
    }
}
