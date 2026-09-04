<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\StoreClassMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreClassMarkAction
{
    use ApiResponse;

    public function handle(StoreClassMarkRequest $request, School $school): JsonResponse
    {
        try {
            $payload = snake_keys($request->validated());
            $payload['school_id'] = $school->id;
            $payload['home_assignment_score'] ??= 0;
            $payload['project_score'] ??= 0;
            $payload['class_test_score'] ??= 0;
            $payload['class_score'] ??= 0;

            $mark = Mark::query()->create($payload);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create class mark.');
        }

        return $this->success(
            MarkResource::make(
                $mark->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Class mark created successfully.',
            201,
        );
    }
}
