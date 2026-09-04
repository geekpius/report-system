<?php

namespace App\Http\Controllers\Api\MarkSetting;

use App\Actions\Api\MarkSetting\ShowMarkSettingAction;
use App\Actions\Api\MarkSetting\UpdateMarkSettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MarkSetting\ShowMarkSettingRequest;
use App\Http\Requests\Api\MarkSetting\UpdateMarkSettingRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MarkSettingController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/mark-settings',
        summary: 'Get mark settings',
        description: 'Returns the school scoring setup. Creates default total-score settings if none exist.',
        security: [['sanctum' => []]],
        tags: ['Mark Settings'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mark settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Mark settings retrieved successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MarkSetting'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function show(ShowMarkSettingRequest $request, School $school, ShowMarkSettingAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    #[OA\Put(
        path: '/schools/{school}/mark-settings',
        summary: 'Update mark settings',
        description: 'Updates the active scoring mode and both total-score and division-score configurations. divisionTotal and divisionTotalPercent are derived by the backend.',
        security: [['sanctum' => []]],
        tags: ['Mark Settings'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['scoringMode', 'totalScore', 'divisionScore'],
                properties: [
                    new OA\Property(property: 'scoringMode', type: 'string', example: 'total_score'),
                    new OA\Property(
                        property: 'totalScore',
                        required: ['classScorePercent', 'examScorePercent'],
                        properties: [
                            new OA\Property(property: 'classScorePercent', type: 'number', format: 'float', minimum: 0, maximum: 100, example: 50),
                            new OA\Property(property: 'examScorePercent', type: 'number', format: 'float', minimum: 0, maximum: 100, example: 50),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'divisionScore',
                        required: ['classScoreMax', 'homeAssignmentMax', 'projectMax', 'classTestMax', 'examAllocationPercent'],
                        properties: [
                            new OA\Property(property: 'classScoreMax', type: 'number', format: 'float', minimum: 0, example: 15),
                            new OA\Property(property: 'homeAssignmentMax', type: 'number', format: 'float', minimum: 0, example: 15),
                            new OA\Property(property: 'projectMax', type: 'number', format: 'float', minimum: 0, example: 15),
                            new OA\Property(property: 'classTestMax', type: 'number', format: 'float', minimum: 0, example: 15),
                            new OA\Property(property: 'examAllocationPercent', type: 'number', format: 'float', minimum: 0, maximum: 100, example: 50),
                        ],
                        type: 'object'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mark settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Mark settings updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MarkSetting'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(UpdateMarkSettingRequest $request, School $school, UpdateMarkSettingAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }
}
