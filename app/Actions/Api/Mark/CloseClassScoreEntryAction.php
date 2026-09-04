<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\ListMarkEntryRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class CloseClassScoreEntryAction
{
    use ApiResponse;

    public function handle(ListMarkEntryRequest $request, School $school): JsonResponse
    {
        $marks = Mark::query()
            ->where('school_id', $school->id)
            ->where('school_class_id', $request->validated('schoolClassId'))
            ->where('subject_id', $request->validated('subjectId'))
            ->where('term_id', $request->validated('termId'))
            ->where('close_class_score_entry', false)
            ->get();

        Mark::query()
            ->whereIn('id', $marks->modelKeys())
            ->update(['close_class_score_entry' => true]);

        $marks->each(fn (Mark $mark) => $mark->setAttribute('close_class_score_entry', true));

        return $this->success(
            MarkResource::collection(
                $marks->load(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher']),
            ),
            'Class score entry closed successfully.',
        );
    }
}
