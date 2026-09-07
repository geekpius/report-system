<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\StoreClassMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreClassMarkAction
{
    use ApiResponse;

    public function handle(StoreClassMarkRequest $request, School $school): JsonResponse
    {
        try {
            $marks = DB::transaction(function () use ($request, $school) {
                return collect($request->validated('marks'))
                    ->map(function (array $item) use ($school): Mark {
                        $payload = snake_keys($item);
                        $payload['school_id'] = $school->id;
                        $payload['home_assignment_score'] ??= 0;
                        $payload['project_score'] ??= 0;
                        $payload['class_test_score'] ??= 0;
                        $payload['class_score'] ??= 0;

                        return Mark::query()->create($payload);
                    });
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create class marks.');
        }

        return $this->success(
            MarkResource::collection($marks),
            'Class marks created successfully.',
            201,
        );
    }
}
