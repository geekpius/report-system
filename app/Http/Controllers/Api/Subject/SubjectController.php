<?php

namespace App\Http\Controllers\Api\Subject;

use App\Actions\Api\Subject\ListSubjectAction;
use App\Actions\Api\Subject\StoreSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Subject\ListSubjectRequest;
use App\Http\Requests\Api\Subject\StoreSubjectRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function index(ListSubjectRequest $request, School $school, ListSubjectAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    public function store(StoreSubjectRequest $request, School $school, StoreSubjectAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }
}
