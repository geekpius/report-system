<?php

namespace App\Actions\Api\StudentSubject;

use App\Concerns\ApiResponse;
use App\Http\Resources\StudentSubjectResource;
use App\Models\StudentClassEnrollment;
use Illuminate\Http\JsonResponse;

class ListStudentSubjectAction
{
    use ApiResponse;

    public function handle(StudentClassEnrollment $enrollment): JsonResponse
    {
        $studentSubjects = $enrollment->studentSubjects()
            ->with(['subject', 'schoolClass', 'classEnrollment'])
            ->orderBy('created_at')
            ->get();

        return $this->success(
            StudentSubjectResource::collection($studentSubjects),
            'Student subjects retrieved successfully.',
        );
    }
}
