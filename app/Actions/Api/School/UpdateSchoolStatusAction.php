<?php

namespace App\Actions\Api\School;

use App\Concerns\ApiResponse;
use App\Enums\SchoolStatus;
use App\Http\Requests\Api\School\UpdateSchoolStatusRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSchoolStatusAction
{
    use ApiResponse;

    public function handle(UpdateSchoolStatusRequest $request, School $school): JsonResponse
    {
        try {
            $school->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update school status.');
        }

        $message = $school->status === SchoolStatus::Active
            ? 'School is now active.'
            : 'School is now archived.';

        return $this->success(
            SchoolResource::make($school),
            $message,
        );
    }
}
