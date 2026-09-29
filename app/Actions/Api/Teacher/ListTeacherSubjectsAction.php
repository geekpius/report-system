<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class ListTeacherSubjectsAction
{
    use ApiResponse;

    public function handle(Teacher $teacher): JsonResponse
    {
        $subjects = Subject::query()
            ->whereHas(
                'teacherAssignments',
                fn ($query) => $query->where('teacher_id', $teacher->id),
            )
            ->orderBy('name')
            ->get();

        return $this->success(
            SubjectResource::collection($subjects),
            'Teacher subjects retrieved successfully.',
        );
    }
}
