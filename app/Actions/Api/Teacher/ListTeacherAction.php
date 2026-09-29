<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\TeacherResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListTeacherAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $teachers = paginate(
            $school->teachers()
                ->with('client')
                ->orderBy('staff_number')
        );

        return $this->success(
            TeacherResource::collection($teachers),
            'Teachers retrieved successfully.',
            meta: pagination_meta($teachers),
        );
    }
}
