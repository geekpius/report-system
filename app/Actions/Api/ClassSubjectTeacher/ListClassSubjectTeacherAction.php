<?php

namespace App\Actions\Api\ClassSubjectTeacher;

use App\Concerns\ApiResponse;
use App\Http\Resources\ClassSubjectTeacherResource;
use App\Models\ClassSubjectTeacher;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListClassSubjectTeacherAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $assignments = ClassSubjectTeacher::query()
            ->whereHas('schoolClass', fn ($query) => $query->where('school_id', $school->id))
            ->with(['schoolClass', 'subject', 'teacher'])
            ->get()
            ->sortBy([
                ['schoolClass.name', 'asc'],
                ['subject.name', 'asc'],
            ])
            ->values();

        return $this->success(
            ClassSubjectTeacherResource::collection($assignments),
            'Class subject teacher assignments retrieved successfully.',
        );
    }
}
