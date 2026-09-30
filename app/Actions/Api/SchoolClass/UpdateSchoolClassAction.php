<?php

namespace App\Actions\Api\SchoolClass;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\SchoolClass\UpdateSchoolClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSchoolClassAction
{
    use ApiResponse;

    public function handle(UpdateSchoolClassRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        try {
            $schoolClass->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update class.');
        }

        return $this->success(
            SchoolClassResource::make($schoolClass),
            'Class updated successfully.',
        );
    }
}
