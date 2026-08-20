<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Email a password reset link when the client exists.
     */
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
