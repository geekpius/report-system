<?php

namespace App\Actions\Api\Mark;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Mark\ListMarkRequest;
use App\Http\Resources\MarkResource;
use App\Models\Mark;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListMarkAction
{
    use ApiResponse;

    public function handle(ListMarkRequest $request, School $school): JsonResponse
    {
        $marks = Mark::query()
            ->whereHas('student', fn ($query) => $query->where('school_id', $school->id))
            ->when($request->validated('studentId'), fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($request->validated('schoolClassId'), fn ($query, $schoolClassId) => $query->where('school_class_id', $schoolClassId))
            ->when($request->validated('termId'), fn ($query, $termId) => $query->where('term_id', $termId))
            ->when($request->validated('subjectId'), fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->when($request->validated('academicYearId'), fn ($query, $academicYearId) => $query->where('academic_year_id', $academicYearId))
            ->with(['student', 'subject', 'schoolClass', 'academicYear', 'term', 'teacher'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success(
            MarkResource::collection($marks),
            'Marks retrieved successfully.',
        );
    }
}
