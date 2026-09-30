<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentSubjectStatus;
use App\Http\Requests\Api\Student\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreStudentAction
{
    use ApiResponse;

    public function handle(StoreStudentRequest $request, School $school): JsonResponse
    {
        $academicYear = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first();

        if ($academicYear === null) {
            return $this->error('No current academic year is set for this school.', 422);
        }

        try {
            $student = DB::transaction(function () use ($request, $school, $academicYear): Student {
                $student = Student::query()->create([
                    'school_id' => $school->id,
                    'school_class_id' => $request->validated('schoolClassId'),
                    'first_name' => $request->validated('firstName'),
                    'middle_name' => $request->validated('middleName'),
                    'last_name' => $request->validated('lastName'),
                    'gender' => $request->validated('gender'),
                    'admission_number' => $request->validated('admissionNumber'),
                    'date_of_birth' => $request->validated('dateOfBirth'),
                ]);

                $enrollment = StudentClassEnrollment::query()->create([
                    'student_id' => $student->id,
                    'school_class_id' => $request->validated('schoolClassId'),
                    'academic_year_id' => $academicYear->id,
                    'status' => EnrollmentStatus::Active,
                    'started_at' => now(),
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

                collect($request->validated('electiveSubjectIds', []))
                    ->each(function (string $subjectId) use ($enrollment): void {
                        StudentSubject::query()->create([
                            'student_id' => $enrollment->student_id,
                            'subject_id' => $subjectId,
                            'school_class_id' => $enrollment->school_class_id,
                            'student_class_enrollment_id' => $enrollment->id,
                            'status' => StudentSubjectStatus::Active,
                        ]);
                    });

                return $student;
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create student.');
        }

        return $this->success(
            StudentResource::make($student),
            'Student created successfully.',
            201,
        );
    }
}
