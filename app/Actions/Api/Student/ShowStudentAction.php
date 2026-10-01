<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class ShowStudentAction
{
    use ApiResponse;

    public function handle(Student $student): JsonResponse
    {
        return $this->success(
            StudentResource::make($student->load([
                'activeClassEnrollment.schoolClass',
                'activeClassEnrollment.academicYear',
            ])),
            'Student retrieved successfully.',
        );
    }
}
