<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Enums\StudentSubjectStatus;
use App\Http\Requests\Api\Student\StoreStudentSubjectsRequest;
use App\Http\Resources\StudentSubjectResource;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreStudentSubjectsAction
{
    use ApiResponse;

    public function handle(StoreStudentSubjectsRequest $request, Student $student): JsonResponse
    {
        $enrollment = $student->activeClassEnrollment;

        if ($enrollment === null) {
            return $this->error('The student does not have an active class enrollment.', 422);
        }

        try {
            $studentSubjects = DB::transaction(function () use ($request, $student, $enrollment) {
                return collect($request->validated('subjectIds'))
                    ->map(function (string $subjectId) use ($student, $enrollment): StudentSubject {
                        $existing = StudentSubject::query()
                            ->where('student_class_enrollment_id', $enrollment->id)
                            ->where('subject_id', $subjectId)
                            ->first();

                        if ($existing !== null) {
                            $existing->update([
                                'status' => StudentSubjectStatus::Active,
                            ]);

                            return $existing->refresh();
                        }

                        return StudentSubject::query()->create([
                            'student_id' => $student->id,
                            'subject_id' => $subjectId,
                            'school_class_id' => $enrollment->school_class_id,
                            'student_class_enrollment_id' => $enrollment->id,
                            'status' => StudentSubjectStatus::Active,
                        ]);
                    })
                    ->values();
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to assign subjects to student.');
        }

        return $this->success(
            StudentSubjectResource::collection($studentSubjects),
            'Subjects assigned to student successfully.',
            201,
        );
    }
}
