<?php

namespace App\Actions\Api\ClassSubjectTeacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\ClassSubjectTeacherResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;

class ListClassSubjectTeacherAction
{
    use ApiResponse;

    public function handle(SchoolClass $schoolClass): JsonResponse
    {
        $assignments = $schoolClass->teacherAssignments()
            ->with(['subject', 'teacher.client'])
            ->get()
            ->sortBy('subject.name')
            ->values();

        return $this->success(
            ClassSubjectTeacherResource::collection($assignments),
            'Class subject teachers retrieved successfully.',
        );
    }
}
