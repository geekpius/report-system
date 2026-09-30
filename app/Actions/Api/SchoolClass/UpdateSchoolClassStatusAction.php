<?php

namespace App\Actions\Api\SchoolClass;

use App\Concerns\ApiResponse;
use App\Enums\SchoolClassStatus;
use App\Http\Requests\Api\SchoolClass\UpdateSchoolClassStatusRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSchoolClassStatusAction
{
    use ApiResponse;

    public function handle(UpdateSchoolClassStatusRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        try {
            $schoolClass->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update class status.');
        }

        $message = $schoolClass->status === SchoolClassStatus::Active
            ? 'Class is now active.'
            : 'Class is now inactive.';

        return $this->success(
            SchoolClassResource::make($schoolClass),
            $message,
        );
    }
}
