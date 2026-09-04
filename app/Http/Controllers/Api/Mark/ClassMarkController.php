<?php

namespace App\Http\Controllers\Api\Mark;

use App\Actions\Api\Mark\CloseClassScoreEntryAction;
use App\Actions\Api\Mark\ListPendingClassMarkAction;
use App\Actions\Api\Mark\ListRecordedClassMarkAction;
use App\Actions\Api\Mark\StoreClassMarkAction;
use App\Actions\Api\Mark\UpdateClassMarkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mark\ListMarkEntryRequest;
use App\Http\Requests\Api\Mark\StoreClassMarkRequest;
use App\Http\Requests\Api\Mark\UpdateClassMarkRequest;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ClassMarkController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/class-marks/pending',
        summary: 'List students with pending class marks',
        description: 'Students who take the subject in the class and term but have no class-score contribution, or a contribution of 0. Marks with closeClassScoreEntry true are excluded. Requires schoolClassId, subjectId, and termId.',
        security: [['sanctum' => []]],
        tags: ['Class Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClassId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'subjectId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'termId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'academicYearId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pending class marks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pending class marks retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MarkEntry')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function pending(
        ListMarkEntryRequest $request,
        School $school,
        ListPendingClassMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Get(
        path: '/schools/{school}/class-marks/recorded',
        summary: 'List students with recorded class marks',
        description: 'Students whose class-score contribution is greater than 0 and closeClassScoreEntry is false.',
        security: [['sanctum' => []]],
        tags: ['Class Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClassId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'subjectId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'termId', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'academicYearId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recorded class marks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Recorded class marks retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MarkEntry')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function recorded(
        ListMarkEntryRequest $request,
        School $school,
        ListRecordedClassMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Post(
        path: '/schools/{school}/class-marks/close',
        summary: 'Close class score entry',
        description: 'Sets closeClassScoreEntry to true for all matching marks. Closed marks are excluded from pending and recorded class-mark lists. Requires schoolClassId, subjectId, and termId.',
        security: [['sanctum' => []]],
        tags: ['Class Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['schoolClassId', 'subjectId', 'termId'],
                properties: [
                    new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'termId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Class score entry closed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class score entry closed successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Mark')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function close(
        ListMarkEntryRequest $request,
        School $school,
        CloseClassScoreEntryAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Post(
        path: '/schools/{school}/class-marks',
        summary: 'Create a class mark',
        description: 'Enter continuous assessment scores. Exam score is stored as 0 until exam marks are submitted. Required class fields depend on the school scoring mode.',
        security: [['sanctum' => []]],
        tags: ['Class Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(ref: '#/components/schemas/StoreClassMarkTotalScoreRequest'),
                    new OA\Schema(ref: '#/components/schemas/StoreClassMarkDivisionScoreRequest'),
                ],
                examples: [
                    new OA\Examples(
                        example: 'total_score',
                        summary: 'Payload when scoring mode is total_score',
                        value: [
                            'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'classScore' => 40,
                        ]
                    ),
                    new OA\Examples(
                        example: 'division_score',
                        summary: 'Payload when scoring mode is division_score',
                        value: [
                            'studentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'subjectId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'schoolClassId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'studentClassEnrollmentId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'academicYearId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'termId' => '3fa85f64-5717-4562-b3fc-2c963f66afa6',
                            'classScore' => 12,
                            'homeAssignmentScore' => 14,
                            'projectScore' => 13,
                            'classTestScore' => 15,
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Class mark created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class mark created successfully.'),
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
        StoreClassMarkRequest $request,
        School $school,
        StoreClassMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/class-marks/{mark}',
        summary: 'Update a class mark',
        description: 'Updates continuous assessment scores without changing the exam score.',
        security: [['sanctum' => []]],
        tags: ['Class Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'mark', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(ref: '#/components/schemas/UpdateClassMarkTotalScoreRequest'),
                    new OA\Schema(ref: '#/components/schemas/UpdateClassMarkDivisionScoreRequest'),
                ],
                examples: [
                    new OA\Examples(
                        example: 'total_score',
                        summary: 'Payload when scoring mode is total_score',
                        value: [
                            'classScore' => 40,
                        ]
                    ),
                    new OA\Examples(
                        example: 'division_score',
                        summary: 'Payload when scoring mode is division_score',
                        value: [
                            'classScore' => 12,
                            'homeAssignmentScore' => 14,
                            'projectScore' => 13,
                            'classTestScore' => 15,
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Class mark updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class mark updated successfully.'),
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
        UpdateClassMarkRequest $request,
        School $school,
        Mark $mark,
        UpdateClassMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $mark);
    }
}
