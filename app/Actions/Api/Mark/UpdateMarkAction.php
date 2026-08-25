<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\UpdateMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateMarkAction
{
    use ApiResponse;

    public function handle(UpdateMarkRequest $request, Mark $mark): JsonResponse
    {
        try {
            $mark->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update mark.');
        }

        return $this->success(
            MarkResource::make(
                $mark->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Mark updated successfully.',
        );
    }
}
