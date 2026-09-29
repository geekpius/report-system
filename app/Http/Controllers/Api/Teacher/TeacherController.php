<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Actions\Api\Teacher\ListTeacherAction;
use App\Actions\Api\Teacher\ListTeacherSubjectsAction;
use App\Actions\Api\Teacher\ResetTeacherPasswordAction;
use App\Actions\Api\Teacher\ShowTeacherAction;
use App\Actions\Api\Teacher\StoreTeacherAction;
use App\Actions\Api\Teacher\UpdateTeacherAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Teacher\ListTeacherRequest;
use App\Http\Requests\Api\Teacher\ListTeacherSubjectsRequest;
use App\Http\Requests\Api\Teacher\ResetTeacherPasswordRequest;
use App\Http\Requests\Api\Teacher\ShowTeacherRequest;
use App\Http\Requests\Api\Teacher\StoreTeacherRequest;
use App\Http\Requests\Api\Teacher\UpdateTeacherRequest;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class TeacherController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/teachers',
        summary: 'List teachers for a school',
        description: 'Returns a paginated list of teachers belonging to the authenticated owner\'s school.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Teachers retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teachers retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Teacher')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListTeacherRequest $request, School $school, ListTeacherAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/teachers',
        summary: 'Create a teacher',
        description: 'Creates a teacher client account (role: teacher, status: active) and school teacher record. A password reset link is emailed so the teacher can set their password.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'staffNumber', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Kwame Mensah'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'teacher@example.com'),
                    new OA\Property(property: 'staffNumber', type: 'string', maxLength: 255, example: 'STF-1001'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000001'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Teacher created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teacher created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Teacher'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreTeacherRequest $request, School $school, StoreTeacherAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }

    #[OA\Get(
        path: '/schools/{school}/teachers/{teacher}',
        summary: 'Get a teacher',
        description: 'Returns a single teacher belonging to the authenticated owner\'s school.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'teacher', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Teacher retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teacher retrieved successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Teacher'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function show(
        ShowTeacherRequest $request,
        School $school,
        Teacher $teacher,
        ShowTeacherAction $action,
    ): JsonResponse {
        return $action->handle($teacher);
    }

    #[OA\Get(
        path: '/schools/{school}/teachers/{teacher}/subjects',
        summary: 'List subjects taught by a teacher',
        description: 'Returns distinct subjects the teacher is currently assigned to teach via class subject assignments.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'teacher', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Teacher subjects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teacher subjects retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Subject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function subjects(
        ListTeacherSubjectsRequest $request,
        School $school,
        Teacher $teacher,
        ListTeacherSubjectsAction $action,
    ): JsonResponse {
        return $action->handle($teacher);
    }

    #[OA\Put(
        path: '/schools/{school}/teachers/{teacher}',
        summary: 'Update teacher information',
        description: 'Updates teacher name and phone for a school owned by the authenticated owner.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'teacher', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Kwame Mensah'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000001'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Teacher updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Teacher updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Teacher'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(
        UpdateTeacherRequest $request,
        School $school,
        Teacher $teacher,
        UpdateTeacherAction $action,
    ): JsonResponse {
        return $action->handle($request, $teacher);
    }

    #[OA\Post(
        path: '/schools/{school}/teachers/{teacher}/reset-password',
        summary: 'Reset teacher password',
        description: 'Sends a password reset link to the teacher\'s client email address.',
        security: [['sanctum' => []]],
        tags: ['Teachers'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'teacher', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset link sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password reset link sent successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Teacher'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function resetPassword(
        ResetTeacherPasswordRequest $request,
        School $school,
        Teacher $teacher,
        ResetTeacherPasswordAction $action,
    ): JsonResponse {
        return $action->handle($teacher);
    }
}
