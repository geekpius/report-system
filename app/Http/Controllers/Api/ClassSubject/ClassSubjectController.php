<?php

namespace App\Http\Controllers\Api\ClassSubject;

use App\Actions\Api\ClassSubject\ListClassSubjectAction;
use App\Actions\Api\ClassSubject\StoreClassSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClassSubject\ListClassSubjectRequest;
use App\Http\Requests\Api\ClassSubject\StoreClassSubjectRequest;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;

class ClassSubjectController extends Controller
{
    public function index(
        ListClassSubjectRequest $request,
        School $school,
        SchoolClass $schoolClass,
        ListClassSubjectAction $action,
    ): JsonResponse {
        return $action->handle($schoolClass);
    }

    public function store(
        StoreClassSubjectRequest $request,
        School $school,
        SchoolClass $schoolClass,
        StoreClassSubjectAction $action,
    ): JsonResponse {
        return $action->handle($request, $schoolClass);
    }
}
