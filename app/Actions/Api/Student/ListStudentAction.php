<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Http\Resources\StudentResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListStudentAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $students = paginate(
            $school->students()
                ->with('schoolClass')
                ->orderBy('admission_number')
        );

        return $this->success(
            StudentResource::collection($students),
            'Students retrieved successfully.',
            meta: pagination_meta($students),
        );
    }
}
