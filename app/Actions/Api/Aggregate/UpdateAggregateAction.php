<?php

namespace App\Actions\Api\Aggregate;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Aggregate\UpdateAggregateRequest;
use App\Http\Resources\AggregateResource;
use App\Models\Aggregate;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateAggregateAction
{
    use ApiResponse;

    public function handle(UpdateAggregateRequest $request, Aggregate $aggregate): JsonResponse
    {
        try {
            $aggregate->update(snake_keys($request->validated()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update aggregate.');
        }

        return $this->success(
            AggregateResource::make($aggregate),
            'Aggregate updated successfully.',
        );
    }
}
