<?php

namespace App\Actions\Api\SchoolClass;

use App\Concerns\ApiResponse;
use App\Http\Resources\SchoolClassResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListSchoolClassAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $classes = $school->classes()->with('classTeacher')->orderBy('name')->get();

        return $this->success(
            SchoolClassResource::collection($classes),
            'Classes retrieved successfully.',
        );
    }
}
