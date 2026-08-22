<?php

namespace App\Http\Controllers\Api\Aggregate;

use App\Actions\Api\Aggregate\ListAggregateAction;
use App\Actions\Api\Aggregate\StoreAggregateAction;
use App\Actions\Api\Aggregate\UpdateAggregateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Aggregate\ListAggregateRequest;
use App\Http\Requests\Api\Aggregate\StoreAggregateRequest;
use App\Http\Requests\Api\Aggregate\UpdateAggregateRequest;
use App\Models\Aggregate;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class AggregateController extends Controller
{
    public function index(
        ListAggregateRequest $request,
        School $school,
        ListAggregateAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    public function store(
        StoreAggregateRequest $request,
        School $school,
        StoreAggregateAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    public function update(
        UpdateAggregateRequest $request,
        School $school,
        Aggregate $aggregate,
        UpdateAggregateAction $action,
    ): JsonResponse {
        return $action->handle($request, $aggregate);
    }
}
