<?php

namespace App\Actions\Api\Subject;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Subject\StoreSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreSubjectAction
{
    use ApiResponse;

    public function handle(StoreSubjectRequest $request, School $school): JsonResponse
    {
        try {
            $subject = Subject::query()->create([
                'school_id' => $school->id,
                ...snake_keys($request->validated()),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create subject.');
        }

        return $this->success(
            SubjectResource::make($subject),
            'Subject created successfully.',
            201,
        );
    }
}
