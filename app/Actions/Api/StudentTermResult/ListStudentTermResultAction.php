<?php

namespace App\Actions\Api\StudentTermResult;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\StudentTermResult\ListStudentTermResultRequest;
use App\Http\Resources\StudentTermResultResource;
use App\Models\School;
use App\Models\StudentTermResult;
use Illuminate\Http\JsonResponse;

class ListStudentTermResultAction
{
    use ApiResponse;

    public function handle(ListStudentTermResultRequest $request, School $school): JsonResponse
    {
        $results = StudentTermResult::query()
            ->whereHas('student', fn ($query) => $query->where('school_id', $school->id))
            ->when($request->validated('studentId'), fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($request->validated('schoolClassId'), fn ($query, $schoolClassId) => $query->where('school_class_id', $schoolClassId))
            ->when($request->validated('termId'), fn ($query, $termId) => $query->where('term_id', $termId))
            ->when($request->validated('academicYearId'), fn ($query, $academicYearId) => $query->where('academic_year_id', $academicYearId))
            ->with(['student', 'schoolClass', 'classEnrollment', 'academicYear', 'term'])
            ->orderBy('class_position')
            ->orderByDesc('average_score')
            ->get();

        return $this->success(
            StudentTermResultResource::collection($results),
            'Student term results retrieved successfully.',
        );
    }
}
