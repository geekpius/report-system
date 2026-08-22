<?php

namespace App\Actions\Api\AcademicYear;

use App\Concerns\ApiResponse;
use App\Http\Resources\AcademicYearResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListAcademicYearAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $academicYears = $school->academicYears()
            ->with('terms')
            ->orderByDesc('starts_on')
            ->get();

        return $this->success(
            AcademicYearResource::collection($academicYears),
            'Academic years retrieved successfully.',
        );
    }
}
