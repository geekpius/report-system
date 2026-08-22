<?php

namespace App\Actions\Api\StudentSubject;

use App\Concerns\ApiResponse;
use App\Enums\StudentSubjectStatus;
use App\Http\Requests\Api\StudentSubject\StoreStudentSubjectRequest;
use App\Http\Resources\StudentSubjectResource;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreStudentSubjectAction
{
    use ApiResponse;

    public function handle(
        StoreStudentSubjectRequest $request,
        StudentClassEnrollment $enrollment,
    ): JsonResponse {
        try {
            $studentSubjects = DB::transaction(function () use ($request, $enrollment) {
                return collect($request->validated('subjects'))
                    ->map(fn (array $subject) => StudentSubject::query()->create([
                        'student_id' => $enrollment->student_id,
                        'subject_id' => $subject['subjectId'],
                        'school_class_id' => $enrollment->school_class_id,
                        'student_class_enrollment_id' => $enrollment->id,
                        'status' => StudentSubjectStatus::Active,
                    ]))
                    ->each(fn (StudentSubject $studentSubject) => $studentSubject->load(['subject', 'schoolClass', 'classEnrollment']))
                    ->values();
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to assign subjects to student.');
        }

        return $this->success(
            StudentSubjectResource::collection($studentSubjects),
            'Elective subjects assigned to student successfully.',
            201,
        );
    }
}
