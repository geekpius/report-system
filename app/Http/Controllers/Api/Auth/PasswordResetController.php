<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;

class PasswordResetController extends Controller
{
    /**
     * Email a password reset link when the client exists.
     */
    #[OA\Post(
        path: '/auth/forgot-password',
        summary: 'Request password reset link',
        description: 'Emails a password reset link when the client email exists.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset link sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'We sent a reset link.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker('clients')->sendResetLink(
            $request->only('email'),
        );

        return $this->success(message: 'We sent a reset link.');
    }

    /**
     * Reset the client password using a valid token.
     */
    #[OA\Post(
        path: '/auth/reset-password',
        summary: 'Reset password',
        description: 'Resets the client password using a valid reset token and revokes existing tokens.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'owner@example.com'),
                    new OA\Property(property: 'token', type: 'string', example: 'reset-token-from-email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password1!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password reset successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid token or validation error',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiError'),
                        new OA\Schema(ref: '#/components/schemas/ValidationError'),
                    ]
                )
            ),
        ]
    )]
    public function update(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('clients')->reset(
            $request->only('email', 'password', 'token'),
            function (Client $client, string $password): void {
                $client->forceFill([
                    'password' => $password,
                ])->save();

                $client->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(message: 'Password reset successfully.');
    }
}
