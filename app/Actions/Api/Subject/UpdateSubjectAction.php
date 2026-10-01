<?php

namespace App\Actions\Api\Subject;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Subject\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSubjectAction
{
    use ApiResponse;

    public function handle(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        try {
            $subject->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update subject.');
        }

        return $this->success(
            SubjectResource::make($subject),
            'Subject updated successfully.',
        );
    }
}
