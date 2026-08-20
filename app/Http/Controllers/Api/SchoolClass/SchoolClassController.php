<?php

namespace App\Http\Controllers\Api\SchoolClass;

use App\Actions\Api\SchoolClass\ListSchoolClassAction;
use App\Actions\Api\SchoolClass\StoreSchoolClassAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SchoolClass\ListSchoolClassRequest;
use App\Http\Requests\Api\SchoolClass\StoreSchoolClassRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class SchoolClassController extends Controller
{
    public function index(ListSchoolClassRequest $request, School $school, ListSchoolClassAction $action): JsonResponse
    {
        return $action->handle($school);
    }

    public function store(StoreSchoolClassRequest $request, School $school, StoreSchoolClassAction $action): JsonResponse
    {
        return $action->handle($request, $school);
    }
}
