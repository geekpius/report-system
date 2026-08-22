<?php

namespace App\Actions\Api\ClassSubjectTeacher;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\ClassSubjectTeacher\StoreClassSubjectTeacherRequest;
use App\Http\Resources\ClassSubjectTeacherResource;
use App\Models\ClassSubjectTeacher;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreClassSubjectTeacherAction
{
    use ApiResponse;

    public function handle(StoreClassSubjectTeacherRequest $request, School $school): JsonResponse
    {
        $validated = $request->validated();

        try {
            $assignments = DB::transaction(function () use ($validated) {
                return collect($validated['subjectIds'])
                    ->map(fn (string $subjectId) => ClassSubjectTeacher::query()->create([
                        'school_class_id' => $validated['schoolClassId'],
                        'subject_id' => $subjectId,
                        'teacher_id' => $validated['teacherId'],
                    ]))
                    ->each(fn (ClassSubjectTeacher $assignment) => $assignment->load(['schoolClass', 'subject', 'teacher']))
                    ->values();
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to assign teacher to subjects.');
        }

        return $this->success(
            ClassSubjectTeacherResource::collection($assignments),
            'Teacher assigned to subjects successfully.',
            201,
        );
    }
}
