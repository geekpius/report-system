<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UpdateStudentController extends Controller
{
    /**
     * Update the authenticated student's profile.
     */
    #[OA\Put(
        path: '/auth/profile/students/{student}',
        summary: 'Update student profile',
        description: 'Updates the authenticated student\'s profile. Requires student ability.',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'gender', 'admissionNumber', 'dateOfBirth'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', maxLength: 255, example: 'Akosua'),
                    new OA\Property(property: 'lastName', type: 'string', maxLength: 255, example: 'Mensah'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
                    new OA\Property(property: 'admissionNumber', type: 'string', maxLength: 255, example: 'ADM-1001'),
                    new OA\Property(property: 'dateOfBirth', type: 'string', format: 'date', example: '2012-04-15'),
                    new OA\Property(property: 'schoolClassId', type: 'string', format: 'uuid', nullable: true),
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
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update(snake_keys($request->validated()));

        return $this->success(
            StudentResource::make($student),
            'Student updated successfully.',
        );
    }
}
