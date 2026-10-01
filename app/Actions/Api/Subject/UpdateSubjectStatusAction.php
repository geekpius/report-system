<?php

namespace App\Actions\Api\Subject;

use App\Concerns\ApiResponse;
use App\Enums\SubjectStatus;
use App\Http\Requests\Api\Subject\UpdateSubjectStatusRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateSubjectStatusAction
{
    use ApiResponse;

    public function handle(UpdateSubjectStatusRequest $request, Subject $subject): JsonResponse
    {
        try {
            $subject->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update subject status.');
        }

        $message = $subject->status === SubjectStatus::Active
            ? 'Subject is now active.'
            : 'Subject is now inactive.';

        return $this->success(
            SubjectResource::make($subject),
            $message,
        );
    }
}
