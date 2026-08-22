<?php

namespace App\Actions\Api\Aggregate;

use App\Concerns\ApiResponse;
use App\Http\Resources\AggregateResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListAggregateAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        $aggregates = $school->aggregates()
            ->orderByDesc('min_score')
            ->get();

        return $this->success(
            AggregateResource::collection($aggregates),
            'Aggregates retrieved successfully.',
        );
    }
}
