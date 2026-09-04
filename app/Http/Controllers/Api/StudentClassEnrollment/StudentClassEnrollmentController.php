<?php

namespace App\Http\Controllers\Api\StudentClassEnrollment;

use App\Actions\Api\StudentClassEnrollment\ListStudentClassEnrollmentAction;
use App\Actions\Api\StudentClassEnrollment\StoreStudentClassEnrollmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StudentClassEnrollment\ListStudentClassEnrollmentRequest;
use App\Http\Requests\Api\StudentClassEnrollment\StoreStudentClassEnrollmentRequest;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class StudentClassEnrollmentController extends Controller
{
    #[OA\Get(
        path: '/schools/{school}/students/{student}/class-enrollments',
        summary: 'List student class enrollments',
        security: [['sanctum' => []]],
        tags: ['Student Class Enrollments'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Enrollments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student class enrollments retrieved successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StudentClassEnrollment')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(
        ListStudentClassEnrollmentRequest $request,
        School $school,
        Student $student,
        ListStudentClassEnrollmentAction $action,
    ): JsonResponse {
        return $action->handle($student);
    }

    #[OA\Post(
        path: '/schools/{school}/students/{student}/class-enrollments',
        summary: 'Enroll a student in a class',
        description: 'Creates an active enrollment and auto-assigns mandatory class subjects.',
        security: [['sanctum' => []]],
        tags: ['Student Class Enrollments'],
        parameters: [
            new OA\Parameter(name: 'school', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'student', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['schoolClassId', 'academicYearId'],
                properties: [
                    new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'academicYearId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'startedAt', type: 'string', format: 'date', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Enrollment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Student class enrollment created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/StudentClassEnrollment'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(
        StoreStudentClassEnrollmentRequest $request,
        School $school,
        Student $student,
        StoreStudentClassEnrollmentAction $action,
    ): JsonResponse {
        return $action->handle($request, $student);
    }
}
