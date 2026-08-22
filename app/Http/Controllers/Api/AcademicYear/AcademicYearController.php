<?php

namespace App\Http\Controllers\Api\AcademicYear;

use App\Actions\Api\AcademicYear\ListAcademicYearAction;
use App\Actions\Api\AcademicYear\StoreAcademicYearAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcademicYear\ListAcademicYearRequest;
use App\Http\Requests\Api\AcademicYear\StoreAcademicYearRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class AcademicYearController extends Controller
{
    public function index(
        ListAcademicYearRequest $request,
        School $school,
        ListAcademicYearAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    public function store(
        StoreAcademicYearRequest $request,
        School $school,
        StoreAcademicYearAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
