<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateStudentAction
{
    use ApiResponse;

    public function handle(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        try {
            $student->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update student.');
        }

        return $this->success(
            StudentResource::make($student),
            'Student updated successfully.',
        );
    }
}
