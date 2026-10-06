<?php

namespace App\Http\Controllers\Api\ClassSubjectTeacher;

use App\Actions\Api\ClassSubjectTeacher\ListClassSubjectTeacherAction;
use App\Actions\Api\ClassSubjectTeacher\StoreClassSubjectTeacherAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClassSubjectTeacher\ListClassSubjectTeacherRequest;
use App\Http\Requests\Api\ClassSubjectTeacher\StoreClassSubjectTeacherRequest;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ClassSubjectTeacherController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/classes/{schoolClass}/subject-teachers',
        summary: "List a class's subject teachers",
        description: 'Returns every subject-teacher assignment for the class, sorted by subject name.',
        security: [['sanctum' => []]],
        tags: ['Class Subject Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClass', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Class subject teachers retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Class subject teachers retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClassSubjectTeacher')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListClassSubjectTeacherRequest $request,
        School $school,
        SchoolClass $schoolClass,
        ListClassSubjectTeacherAction $action,
    ): JsonResponse {
        return $action->handle($schoolClass);
    }

    #[OA\Post(
        path: '/schools/{school}/classes/{schoolClass}/subject-teachers',
        summary: 'Assign a teacher to subjects in a class',
        security: [['sanctum' => []]],
        tags: ['Class Subject Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'schoolClass', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subjectIds', 'teacherId'],
                properties: [
                    new OA\Property(
                        property: 'subjectIds',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(type: 'string', format: 'uuid')
                    ),
                    new OA\Property(property: 'teacherId', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Assignments created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teacher assigned to subjects successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ClassSubjectTeacher')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreClassSubjectTeacherRequest $request,
        School $school,
        SchoolClass $schoolClass,
        StoreClassSubjectTeacherAction $action,
    ): JsonResponse {
        return $action->handle($request, $schoolClass);
    }
}
