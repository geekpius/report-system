<?php

namespace App\Actions\Api\SchoolClass;

use App\Concerns\ApiResponse;
use App\Enums\SchoolClassStatus;
use App\Http\Requests\Api\SchoolClass\StoreSchoolClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreSchoolClassAction
{
    use ApiResponse;

    public function handle(StoreSchoolClassRequest $request, School $school): JsonResponse
    {
        try {
            $class = SchoolClass::query()->create([
                'school_id' => $school->id,
                'status' => SchoolClassStatus::Active,
                ...snake_keys($request->validated()),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create class.');
        }

        return $this->success(
            SchoolClassResource::make($class),
            'Class created successfully.',
            201,
        );
    }
}
