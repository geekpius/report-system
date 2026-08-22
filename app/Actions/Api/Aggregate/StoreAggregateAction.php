<?php

namespace App\Actions\Api\Aggregate;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Aggregate\StoreAggregateRequest;
use App\Http\Resources\AggregateResource;
use App\Models\Aggregate;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class StoreAggregateAction
{
    use ApiResponse;

    public function handle(StoreAggregateRequest $request, School $school): JsonResponse
    {
        try {
            $aggregate = Aggregate::query()->create([
                'school_id' => $school->id,
                ...snake_keys($request->validated()),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create aggregate.');
        }

        return $this->success(
            AggregateResource::make($aggregate),
            'Aggregate created successfully.',
            201,
        );
    }
}
