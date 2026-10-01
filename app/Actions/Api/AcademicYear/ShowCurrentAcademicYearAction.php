<?php

namespace App\Actions\Api\AcademicYear;

use App\Concerns\ApiResponse;
use App\Http\Resources\AcademicYearResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ShowCurrentAcademicYearAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $academicYear = $school->currentAcademicYear()->with('terms')->first();

        if ($academicYear === null) {
            return $this->error('No current academic year is set for this school.', 404);
        }

        return $this->success(
            AcademicYearResource::make($academicYear),
            'Current academic year retrieved successfully.',
        );
    }
}
