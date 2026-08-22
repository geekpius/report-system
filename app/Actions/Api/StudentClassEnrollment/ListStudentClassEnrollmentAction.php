<?php

namespace App\Actions\Api\StudentClassEnrollment;

use App\Concerns\ApiResponse;
use App\Http\Resources\StudentClassEnrollmentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class ListStudentClassEnrollmentAction
{
    use ApiResponse;

    public function handle(Student $student): JsonResponse
    {
        $enrollments = $student->classEnrollments()
            ->with(['schoolClass', 'academicYear', 'studentSubjects.subject'])
            ->orderByDesc('started_at')
            ->get();

        return $this->success(
            StudentClassEnrollmentResource::collection($enrollments),
            'Student class enrollments retrieved successfully.',
        );
    }
}
