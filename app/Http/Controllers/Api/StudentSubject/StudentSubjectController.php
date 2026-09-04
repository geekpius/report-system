<?php

namespace App\Http\Controllers\Api\StudentSubject;

use App\Actions\Api\StudentSubject\ListStudentSubjectAction;
use App\Actions\Api\StudentSubject\StoreStudentSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StudentSubject\ListStudentSubjectRequest;
use App\Http\Requests\Api\StudentSubject\StoreStudentSubjectRequest;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class StudentSubjectController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/students/{student}/class-enrollments/{studentClassEnrollment}/subjects',
        summary: 'List student subjects for an enrollment',
        security: [['sanctum' => []]],
        tags: ['Student Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'studentClassEnrollment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
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
    public function index(
        ListStudentSubjectRequest $request,
        School $school,
        Student $student,
        StudentClassEnrollment $studentClassEnrollment,
        ListStudentSubjectAction $action,
    ): JsonResponse {
        return $action->handle($studentClassEnrollment);
    }

    #[OA\Post(
        path: '/schools/{school}/students/{student}/class-enrollments/{studentClassEnrollment}/subjects',
        summary: 'Assign elective subjects to a student enrollment',
        description: 'Adds elective (non-mandatory) subjects to an active enrollment.',
        security: [['sanctum' => []]],
        tags: ['Student Subjects'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'studentClassEnrollment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subjects'],
                properties: [
                    new OA\Property(
                        property: 'subjects',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            required: ['subjectId'],
                            properties: [
                                new OA\Property(property: 'subjectId', type: 'string', format: 'uuid'),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Student subjects created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student subjects created successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentSubject')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreStudentSubjectRequest $request,
        School $school,
        Student $student,
        StudentClassEnrollment $studentClassEnrollment,
        StoreStudentSubjectAction $action,
    ): JsonResponse {
        return $action->handle($request, $studentClassEnrollment);
    }
}
