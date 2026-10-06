<?php

namespace App\Actions\Api\ClassSubject;

use App\Concerns\ApiResponse;
use App\Http\Resources\ClassSubjectResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;

class ListClassSubjectAction
{
    use ApiResponse;

    public function handle(SchoolClass $schoolClass): JsonResponse
    {
        $classSubjects = $schoolClass->classSubjects()
            ->with('subject')
            ->get()
            ->sortBy([
                ['is_mandatory', 'desc'],
                ['subject.name', 'asc'],
            ])
            ->values();

        return $this->success(
            ClassSubjectResource::collection($classSubjects),
            'Class subjects retrieved successfully.',
        );
    }
}
