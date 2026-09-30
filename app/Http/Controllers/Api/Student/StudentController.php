<?php

namespace App\Http\Controllers\Api\Student;

use App\Actions\Api\Student\ListStudentAction;
use App\Actions\Api\Student\ListStudentSubjectsAction;
use App\Actions\Api\Student\ShowStudentAction;
use App\Actions\Api\Student\StoreStudentAction;
use App\Actions\Api\Student\UpdateStudentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\ListStudentRequest;
use App\Http\Requests\Api\Student\ListStudentSubjectsRequest;
use App\Http\Requests\Api\Student\ShowStudentRequest;
use App\Http\Requests\Api\Student\StoreStudentRequest;
use App\Http\Requests\Api\Student\UpdateStudentRequest;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class StudentController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/students',
        summary: 'List students for a school',
        description: 'Returns a paginated list of students belonging to the authenticated owner\'s school.',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Students retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Students retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Student')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListStudentRequest $request, School $school, ListStudentAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    #[OA\Post(
        path: '/schools/{school}/students',
        summary: 'Admit a student',
        description: 'Creates a student, enrolls them in a class for an academic year, auto-assigns mandatory subjects, and optionally assigns elective subjects.',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['admissionNumber', 'firstName', 'lastName', 'gender', 'dateOfBirth', 'schoolClassId', 'academicYearId'],
                properties: [
                    new OA\Property(property: 'admissionNumber', type: 'string', maxLength: 255, example: 'ADM-2026-001'),
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255, example: 'John'),
                    new OA\Property(property: 'middleName', type: 'string', maxLength: 255, nullable: true, example: 'Kwame'),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255, example: 'Doe'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'dateOfBirth', type: 'string', format: 'date', example: '2012-04-15'),
                    new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
                    new OA\Property(
                        property: 'electiveSubjectIds',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'Optional. Only subjects offered as electives on the class menu can be selected.',
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Student created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Student'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreStudentRequest $request, School $school, StoreStudentAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }

    #[OA\Get(
        path: '/schools/{school}/students/{student}',
        summary: 'Get a student',
        description: 'Returns a single student belonging to the authenticated owner\'s school.',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student retrieved successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Student'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function show(
        ShowStudentRequest $request,
        School $school,
        Student $student,
        ShowStudentAction $action,
    ): JsonResponse {
        return $action->handle($student);
    }

    #[OA\Put(
        path: '/schools/{school}/students/{student}',
        summary: 'Update student information',
        description: 'Updates student personal details for a school owned by the authenticated owner. Does not change class enrollment or subjects.',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'gender', 'dateOfBirth'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255, example: 'John'),
                    new OA\Property(property: 'middleName', type: 'string', maxLength: 255, nullable: true, example: 'Kwame'),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255, example: 'Doe'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                    new OA\Property(property: 'dateOfBirth', type: 'string', format: 'date', example: '2012-04-15'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Student'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(
        UpdateStudentRequest $request,
        School $school,
        Student $student,
        UpdateStudentAction $action,
    ): JsonResponse {
        return $action->handle($request, $student);
    }

    #[OA\Get(
        path: '/schools/{school}/students/{student}/subjects',
        summary: 'List subjects a student is enrolled in',
        description: 'Returns active subject enrollments for the student.',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student subjects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student subjects retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentSubject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function subjects(
        ListStudentSubjectsRequest $request,
        School $school,
        Student $student,
        ListStudentSubjectsAction $action,
    ): JsonResponse {
        return $action->handle($student);
    }
}
