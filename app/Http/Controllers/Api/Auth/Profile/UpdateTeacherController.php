<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UpdateTeacherController extends Controller
{
    /**
     * Update the authenticated teacher's profile.
     */
    #[OA\Put(
        path: '/auth/profile/teachers/{teacher}',
        summary: 'Update teacher profile',
        description: 'Updates the authenticated teacher\'s profile. Requires teacher ability.',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [
            new OA\Parameter(
                name: 'teacher',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['staffNumber', 'phone'],
                properties: [
                    new OA\Property(property: 'staffNumber', type: 'string', maxLength: 255, example: 'STF-1001'),
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
    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher->update(snake_keys($request->validated()));

        return $this->success(
            TeacherResource::make($teacher),
            'Teacher updated successfully.',
        );
    }
}
