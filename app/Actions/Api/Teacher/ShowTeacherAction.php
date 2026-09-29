<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class ShowTeacherAction
{
    use ApiResponse;

    public function handle(Teacher $teacher): JsonResponse
    {
        return $this->success(
            TeacherResource::make($teacher->load('client')),
            'Teacher retrieved successfully.',
        );
    }
}
