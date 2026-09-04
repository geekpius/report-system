<?php

namespace App\Http\Controllers\Api\Mark;

use App\Actions\Api\Mark\ListMarkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mark\ListMarkRequest;
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
}
