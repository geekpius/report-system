<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\UpsertExamMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpsertExamMarkAction
{
    use ApiResponse;

    public function handle(UpsertExamMarkRequest $request, School $school): JsonResponse
    {
        try {
            $payload = snake_keys($request->validated());
            $existing = Mark::query()
                ->where('school_id', $school->id)
                ->where('student_class_enrollment_id', $payload['student_class_enrollment_id'])
                ->where('subject_id', $payload['subject_id'])
                ->where('term_id', $payload['term_id'])
                ->first();

            if ($existing !== null) {
                if ($existing->close_exam_score_entry) {
                    return $this->error('Exam score entry is closed for this mark.', 422);
                }

                $existing->update([
                    'exam_score' => $payload['exam_score'] ?? 0,
                    'participated' => $payload['participated'],
                    'teacher_id' => $payload['teacher_id'] ?? $existing->teacher_id,
                ]);

                return $this->success(
                    MarkResource::make(
                        $existing->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
                    ),
                    'Exam mark updated successfully.',
                );
            }

            $payload['school_id'] = $school->id;
            $payload['exam_score'] ??= 0;

            $mark = Mark::query()->create($payload);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to save exam mark.');
        }

        return $this->success(
            MarkResource::make(
                $mark->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Exam mark created successfully.',
            201,
        );
    }
}
