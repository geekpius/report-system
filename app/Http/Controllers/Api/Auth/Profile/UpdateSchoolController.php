<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UpdateSchoolController extends Controller
{
    /**
     * Update the authenticated owner's school.
     */
    #[OA\Put(
        path: '/auth/profile/schools/{school}',
        summary: 'Update school profile',
        description: 'Updates the authenticated owner\'s school. Requires owner ability.',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [
            new OA\Parameter(
                name: 'school',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'address', 'city', 'type', 'phone'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Ridge SHS'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 255, example: '12 Independence Ave'),
                    new OA\Property(property: 'city', type: 'string', maxLength: 255, example: 'Accra'),
                    new OA\Property(property: 'type', type: 'string', enum: ['private', 'public'], example: 'private'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 255, example: '0240000000'),
                    new OA\Property(property: 'imageUrl', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'motto', type: 'string', maxLength: 255, nullable: true, example: 'Excellence'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'School updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'School updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/School'),
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
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $school->update(snake_keys($request->validated()));

        return $this->success(
            SchoolResource::make($school),
            'School updated successfully.',
        );
    }
}
