<?php

namespace App\Http\Controllers\Api\StudentClassEnrollment;

use App\Actions\Api\StudentClassEnrollment\ListStudentClassEnrollmentAction;
use App\Actions\Api\StudentClassEnrollment\StoreStudentClassEnrollmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StudentClassEnrollment\ListStudentClassEnrollmentRequest;
use App\Http\Requests\Api\StudentClassEnrollment\StoreStudentClassEnrollmentRequest;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentClassEnrollmentController extends Controller
{
    public function index(
        ListStudentClassEnrollmentRequest $request,
        School $school,
        Student $student,
        ListStudentClassEnrollmentAction $action,
    ): JsonResponse {
        return $action->handle($student);
    }

    public function store(
        StoreStudentClassEnrollmentRequest $request,
        School $school,
        Student $student,
        StoreStudentClassEnrollmentAction $action,
    ): JsonResponse {
        return $action->handle($request, $student);
    }
}
