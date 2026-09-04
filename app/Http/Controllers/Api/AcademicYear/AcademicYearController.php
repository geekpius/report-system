<?php

namespace App\Http\Controllers\Api\AcademicYear;

use App\Actions\Api\AcademicYear\ListAcademicYearAction;
use App\Actions\Api\AcademicYear\StoreAcademicYearAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcademicYear\ListAcademicYearRequest;
use App\Http\Requests\Api\AcademicYear\StoreAcademicYearRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AcademicYearController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/academic-years',
        summary: 'List academic years',
        security: [['sanctum' => []]],
        tags: ['Academic Years'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Academic years retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Academic years retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AcademicYear')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListAcademicYearRequest $request,
        School $school,
        ListAcademicYearAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/academic-years',
        summary: 'Create an academic year',
        security: [['sanctum' => []]],
        tags: ['Academic Years'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'startsOn', 'endsOn'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: '2025/2026'),
                    new OA\Property(property: 'startsOn', type: 'string', format: 'date', example: '2025-09-01'),
                    new OA\Property(property: 'endsOn', type: 'string', format: 'date', example: '2026-07-31'),
                    new OA\Property(property: 'isCurrent', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Academic year created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Academic year created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AcademicYear'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreAcademicYearRequest $request,
        School $school,
        StoreAcademicYearAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
