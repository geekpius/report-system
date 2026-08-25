<?php

namespace App\Http\Controllers\Api\StudentTermResult;

use App\Actions\Api\StudentTermResult\ListStudentTermResultAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StudentTermResult\ListStudentTermResultRequest;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class StudentTermResultController extends Controller
{
    public function index(
        ListStudentTermResultRequest $request,
        School $school,
        ListStudentTermResultAction $action,
    ): JsonResponse {
        return $action->handle($request, $school);
    }
}
