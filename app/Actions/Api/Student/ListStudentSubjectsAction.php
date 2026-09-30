<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Enums\StudentSubjectStatus;
use App\Http\Resources\StudentSubjectResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class ListStudentSubjectsAction
{
    use ApiResponse;

    public function handle(Student $student): JsonResponse
    {
        $studentSubjects = $student->studentSubjects()
            ->where('status', StudentSubjectStatus::Active)
            ->with(['subject', 'schoolClass', 'classEnrollment'])
            ->orderBy('created_at')
            ->get();

        return $this->success(
            StudentSubjectResource::collection($studentSubjects),
            'Student subjects retrieved successfully.',
        );
    }
}
