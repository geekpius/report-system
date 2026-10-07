<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Enums\StudentSubjectStatus;
use App\Http\Resources\StudentSubjectResource;
use App\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Throwable;

class UnassignStudentSubjectAction
{
    use ApiResponse;

    public function handle(StudentSubject $studentSubject): JsonResponse
    {
        try {
            $studentSubject->update([
                'status' => StudentSubjectStatus::Dropped,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to unassign subject from student.');
        }

        return $this->success(
            StudentSubjectResource::make($studentSubject->refresh()),
            'Subject unassigned from student successfully.',
        );
    }
}
