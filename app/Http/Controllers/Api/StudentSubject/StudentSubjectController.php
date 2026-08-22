<?php

namespace App\Http\Controllers\Api\StudentSubject;

use App\Actions\Api\StudentSubject\ListStudentSubjectAction;
use App\Actions\Api\StudentSubject\StoreStudentSubjectAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StudentSubject\ListStudentSubjectRequest;
use App\Http\Requests\Api\StudentSubject\StoreStudentSubjectRequest;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Http\JsonResponse;

class StudentSubjectController extends Controller
{
    public function index(
        ListStudentSubjectRequest $request,
        School $school,
        Student $student,
        StudentClassEnrollment $studentClassEnrollment,
        ListStudentSubjectAction $action,
    ): JsonResponse {
        return $action->handle($studentClassEnrollment);
    }

    public function store(
        StoreStudentSubjectRequest $request,
        School $school,
        Student $student,
        StudentClassEnrollment $studentClassEnrollment,
        StoreStudentSubjectAction $action,
    ): JsonResponse {
        return $action->handle($request, $studentClassEnrollment);
    }
}
