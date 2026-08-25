<?php

namespace App\Http\Controllers\Api\Mark;

use App\Actions\Api\Mark\ListMarkAction;
use App\Actions\Api\Mark\StoreMarkAction;
use App\Actions\Api\Mark\UpdateMarkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mark\ListMarkRequest;
use App\Http\Requests\Api\Mark\StoreMarkRequest;
use App\Http\Requests\Api\Mark\UpdateMarkRequest;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class MarkController extends Controller
{
    public function index(
        ListMarkRequest $request,
        School $school,
        ListMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    public function store(
        StoreMarkRequest $request,
        School $school,
        StoreMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }

    public function update(
        UpdateMarkRequest $request,
        School $school,
        Mark $mark,
        UpdateMarkAction $action,
    ): JsonResponse {
        return $action->handle($request, $mark);
    }
}
