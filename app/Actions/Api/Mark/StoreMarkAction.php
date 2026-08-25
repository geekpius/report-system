<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\StoreMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreMarkAction
{
    use ApiResponse;

    public function handle(StoreMarkRequest $request, School $school): JsonResponse
    {
        try {
            $mark = Mark::query()->create(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create mark.');
        }

        return $this->success(
            MarkResource::make(
                $mark->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Mark created successfully.',
            201,
        );
    }
}
