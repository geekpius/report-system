<?php

namespace App\Http\Controllers\Api\Mark;

use App\Actions\Api\Mark\CloseExamScoreEntryAction;
use App\Actions\Api\Mark\ListPendingExamMarkAction;
use App\Actions\Api\Mark\ListRecordedExamMarkAction;
use App\Actions\Api\Mark\UpsertExamMarkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mark\ListMarkEntryRequest;
use App\Http\Requests\Api\Mark\UpsertExamMarkRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ExamMarkController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/exam-marks/pending',
        summary: 'List students with pending exam marks',
        description: 'Students who take the subject in the class and term but have no exam score, or an exam score of 0. Marks with closeExamScoreEntry true are excluded. Requires schoolClassId, subjectId, and termId.',
        security: [['sanctum' => []]],
        tags: ['Exam Marks'],
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
                description: 'Pending exam marks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pending exam marks retrieved successfully.'),
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
        ListPendingExamMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Get(
        path: '/schools/{school}/exam-marks/recorded',
        summary: 'List students with recorded exam marks',
        description: 'Students whose exam score is greater than 0 and closeExamScoreEntry is false.',
        security: [['sanctum' => []]],
        tags: ['Exam Marks'],
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
                description: 'Recorded exam marks retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Recorded exam marks retrieved successfully.'),
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
        ListRecordedExamMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Post(
        path: '/schools/{school}/exam-marks/close',
        summary: 'Close exam score entry',
        description: 'Sets closeExamScoreEntry to true for all matching marks. Closed marks are excluded from pending and recorded exam-mark lists. Requires schoolClassId, subjectId, and termId.',
        security: [['sanctum' => []]],
        tags: ['Exam Marks'],
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
                description: 'Exam score entry closed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Exam score entry closed successfully.'),
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
        CloseExamScoreEntryAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    #[OA\Put(
        path: '/schools/{school}/exam-marks',
        summary: 'Create or update an exam mark',
        description: 'Updates the exam score when a class mark already exists for the student, subject, and term. Creates the mark with class scores of 0 if it does not exist. participated is required; examScore is required when participated is true. When participated is false, grade is stored as null.',
        security: [['sanctum' => []]],
        tags: ['Exam Marks'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(ref: '#/components/schemas/UpsertExamMarkTotalScoreRequest'),
                    new OA\Schema(ref: '#/components/schemas/UpsertExamMarkDivisionScoreRequest'),
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
                            'participated' => true,
                            'examScore' => 70,
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
                            'participated' => true,
                            'examScore' => 80,
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Exam mark updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Exam mark updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Mark'),
                    ]
                )
            ),
            new OA\Response(
                response: 201,
                description: 'Exam mark created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Exam mark created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Mark'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function upsert(
        UpsertExamMarkRequest $request,
        School $school,
        UpsertExamMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
