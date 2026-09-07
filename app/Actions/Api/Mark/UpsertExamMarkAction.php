<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\UpsertExamMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpsertExamMarkAction
{
    use ApiResponse;

    public function handle(UpsertExamMarkRequest $request, School $school): JsonResponse
    {
        try {
            /** @var array{marks: Collection<int, Mark>, updated: bool} $result */
            $result = DB::transaction(function () use ($request, $school): array {
                $items = collect($request->validated('marks'))->map(fn (array $item): array => snake_keys($item));
                $existing = $this->existingMarks($school, $items);

                if ($existing->contains(fn (Mark $mark): bool => $mark->close_exam_score_entry)) {
                    return ['closed' => true];
                }

                $updated = false;
                $marks = $items->map(function (array $payload) use ($school, $existing, &$updated): Mark {
                    $key = $this->markKey($payload);
                    $mark = $existing->get($key);

                    if ($mark !== null) {
                        $updated = true;
                        $mark->update([
                            'exam_score' => $payload['exam_score'] ?? 0,
                            'participated' => $payload['participated'],
                            'teacher_id' => $payload['teacher_id'] ?? $mark->teacher_id,
                        ]);

                        return $mark;
                    }

                    $payload['school_id'] = $school->id;
                    $payload['exam_score'] ??= 0;

                    return Mark::query()->create($payload);
                });

                return [
                    'closed' => false,
                    'marks' => $marks,
                    'updated' => $updated,
                ];
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to save exam marks.');
        }

        if (($result['closed'] ?? false) === true) {
            return $this->error('Exam score entry is closed for one or more marks.', 422);
        }

        return $this->success(
            MarkResource::collection($result['marks']),
            $result['updated'] ? 'Exam marks updated successfully.' : 'Exam marks created successfully.',
            $result['updated'] ? 200 : 201,
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<string, Mark>
     */
    protected function existingMarks(School $school, Collection $items): Collection
    {
        return Mark::query()
            ->where('school_id', $school->id)
            ->where(function ($query) use ($items): void {
                foreach ($items as $item) {
                    $query->orWhere(function ($query) use ($item): void {
                        $query->where('student_class_enrollment_id', $item['student_class_enrollment_id'])
                            ->where('subject_id', $item['subject_id'])
                            ->where('term_id', $item['term_id']);
                    });
                }
            })
            ->get()
            ->keyBy(fn (Mark $mark): string => $this->markKey([
                'student_class_enrollment_id' => $mark->student_class_enrollment_id,
                'subject_id' => $mark->subject_id,
                'term_id' => $mark->term_id,
            ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function markKey(array $payload): string
    {
        return implode('|', [
            $payload['student_class_enrollment_id'],
            $payload['subject_id'],
            $payload['term_id'],
        ]);
    }
}
