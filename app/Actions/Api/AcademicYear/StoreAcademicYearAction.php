<?php

namespace App\Actions\Api\AcademicYear;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\AcademicYear\StoreAcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreAcademicYearAction
{
    use ApiResponse;

    public function handle(StoreAcademicYearRequest $request, School $school): JsonResponse
    {
        $validated = snake_keys($request->validated());

        try {
            $academicYear = DB::transaction(function () use ($validated, $school) {
                if (($validated['is_current'] ?? false) === true) {
                    AcademicYear::query()
                        ->where('school_id', $school->id)
                        ->update(['is_current' => false]);
                }

                return AcademicYear::query()->create([
                    'school_id' => $school->id,
                    ...$validated,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create academic year.');
        }

        return $this->success(
            AcademicYearResource::make($academicYear),
            'Academic year created successfully.',
            201,
        );
    }
}
