<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Enums\MarkEntryKind;
use App\Http\Requests\Api\Mark\ListMarkEntryRequest;
use App\Http\Resources\MarkEntryResource;
use App\Models\School;
use App\Support\MarkEntryQuery;
use Illuminate\Http\JsonResponse;

class ListRecordedClassMarkAction
{
    use ApiResponse;

    public function handle(ListMarkEntryRequest $request, School $school): JsonResponse
    {
        return $this->success(
            MarkEntryResource::collection(
                (new MarkEntryQuery)->get($request, $school, MarkEntryKind::ClassScore, recorded: true),
            ),
            'Recorded class marks retrieved successfully.',
        );
    }
}
