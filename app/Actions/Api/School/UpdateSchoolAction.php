<?php

namespace App\Actions\Api\School;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\School\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSchoolAction
{
    use ApiResponse;

    public function handle(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        try {
            $school->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update school.');
        }

        return $this->success(
            SchoolResource::make($school),
            'School updated successfully.',
        );
    }
}
