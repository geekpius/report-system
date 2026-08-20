<?php

namespace App\Actions\Api\Subject;

use App\Concerns\ApiResponse;
use App\Http\Resources\SubjectResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListSubjectAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $subjects = $school->subjects()->orderBy('name')->get();

        return $this->success(
            SubjectResource::collection($subjects),
            'Subjects retrieved successfully.',
        );
    }
}
