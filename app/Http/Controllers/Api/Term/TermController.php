<?php

namespace App\Http\Controllers\Api\Term;

use App\Actions\Api\Term\ListTermAction;
use App\Actions\Api\Term\StoreTermAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Term\ListTermRequest;
use App\Http\Requests\Api\Term\StoreTermRequest;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class TermController extends Controller
{
    public function index(
        ListTermRequest $request,
        School $school,
        AcademicYear $academicYear,
        ListTermAction $action,
    ): JsonResponse {
        return $action->handle($academicYear);
    }

    public function store(
        StoreTermRequest $request,
        School $school,
        AcademicYear $academicYear,
        StoreTermAction $action,
    ): JsonResponse {
        return $action->handle($request, $academicYear);
    }
}
