<?php

namespace App\Actions\Api\StudentClassEnrollment;

use App\Concerns\ApiResponse;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Http\Requests\Api\StudentClassEnrollment\StoreStudentClassEnrollmentRequest;
use App\Http\Resources\StudentClassEnrollmentResource;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreStudentClassEnrollmentAction
{
    use ApiResponse;

    public function handle(StoreStudentClassEnrollmentRequest $request, Student $student): JsonResponse
    {
        $validated = snake_keys($request->validated());

        try {
            $enrollment = DB::transaction(function () use ($validated, $student) {
                $enrollment = StudentClassEnrollment::query()->create([
                    'student_id' => $student->id,
                    'school_class_id' => $validated['school_class_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                    'status' => EnrollmentStatus::Active,
                    'started_at' => $validated['started_at'] ?? now(),
                ]);

                $student->update([
                    'school_class_id' => $enrollment->school_class_id,
                ]);

                ClassSubject::query()
                    ->where('school_class_id', $enrollment->school_class_id)
                    ->where('is_mandatory', true)
                    ->pluck('subject_id')
                    ->each(function (string $subjectId) use ($enrollment): void {
                        StudentSubject::query()->create([
                            'student_id' => $enrollment->student_id,
                            'subject_id' => $subjectId,
                            'school_class_id' => $enrollment->school_class_id,
                            'student_class_enrollment_id' => $enrollment->id,
                            'status' => StudentSubjectStatus::Active,
                        ]);
                    });

                return $enrollment;
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create student class enrollment.');
        }

        return $this->success(
            StudentClassEnrollmentResource::make(
                $enrollment->load(['schoolClass', 'academicYear', 'studentSubjects.subject']),
            ),
            'Student class enrollment created successfully.',
            201,
        );
    }
}
