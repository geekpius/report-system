<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\UpdateClassMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateClassMarkAction
{
    use ApiResponse;

    public function handle(UpdateClassMarkRequest $request, Mark $mark): JsonResponse
    {
        try {
            if ($mark->close_class_score_entry) {
                return $this->error('Class score entry is closed for this mark.', 422);
            }

            $mark->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update class mark.');
        }

        return $this->success(
            MarkResource::make(
                $mark->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Class mark updated successfully.',
        );
    }
}
