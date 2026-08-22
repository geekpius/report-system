<?php

namespace App\Http\Controllers\Api\ClassSubjectTeacher;

use App\Actions\Api\ClassSubjectTeacher\ListClassSubjectTeacherAction;
use App\Actions\Api\ClassSubjectTeacher\StoreClassSubjectTeacherAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClassSubjectTeacher\ListClassSubjectTeacherRequest;
use App\Http\Requests\Api\ClassSubjectTeacher\StoreClassSubjectTeacherRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ClassSubjectTeacherController extends Controller
{
    public function index(
        ListClassSubjectTeacherRequest $request,
        School $school,
        ListClassSubjectTeacherAction $action,
    ): JsonResponse {
        return $action->handle($school);
    }

    public function store(
        StoreClassSubjectTeacherRequest $request,
        School $school,
        StoreClassSubjectTeacherAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
