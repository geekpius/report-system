<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Throwable;

class ResetTeacherPasswordAction
{
    use ApiResponse;

    public function handle(Teacher $teacher): JsonResponse
    {
        try {
            $teacher->loadMissing('client');

            Password::broker('clients')->sendResetLink([
                'email' => $teacher->client->email,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to send password reset link.');
        }

        return $this->success(
            TeacherResource::make($teacher),
            'Password reset link sent successfully.',
        );
    }
}
