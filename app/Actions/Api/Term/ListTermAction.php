<?php

namespace App\Actions\Api\Term;

use App\Concerns\ApiResponse;
use App\Http\Resources\TermResource;
use App\Models\AcademicYear;
use Illuminate\Http\JsonResponse;

class ListTermAction
{
    use ApiResponse;

    public function handle(AcademicYear $academicYear): JsonResponse
    {
        $terms = $academicYear->terms()->orderBy('number')->get();

        return $this->success(
            TermResource::collection($terms),
            'Terms retrieved successfully.',
        );
    }
}
