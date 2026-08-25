<?php

namespace App\Support;

use App\Enums\StudentSubjectStatus;
use App\Models\ClassSubject;
use App\Models\Mark;
use App\Models\StudentSubject;
use App\Models\StudentTermResult;
use Illuminate\Support\Collection;

class StudentTermResultCalculator
{
    public static function recalculateFromMark(Mark $mark): void
    {
        self::recalculateForEnrollmentTerm(
            enrollmentId: $mark->student_class_enrollment_id,
            termId: $mark->term_id,
            schoolClassId: $mark->school_class_id,
            academicYearId: $mark->academic_year_id,
            studentId: $mark->student_id,
        );
    }

    public static function recalculateForEnrollmentTerm(
        string $enrollmentId,
        string $termId,
        string $schoolClassId,
        string $academicYearId,
        string $studentId,
    ): void {
        $activeSubjectIds = StudentSubject::query()
            ->where('student_class_enrollment_id', $enrollmentId)
            ->where('status', StudentSubjectStatus::Active)
            ->pluck('subject_id');

        $marks = Mark::query()
            ->where('student_class_enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->whereIn('subject_id', $activeSubjectIds)
            ->get();

        if ($marks->isEmpty()) {
            StudentTermResult::query()
                ->where('student_class_enrollment_id', $enrollmentId)
                ->where('term_id', $termId)
                ->delete();

            self::recalculateClassPositions($schoolClassId, $academicYearId, $termId);

            return;
        }

        $subjectsCount = $marks->count();
        $totalScore = round($marks->sum(fn (Mark $mark) => (float) $mark->total_score), 2);
        $averageScore = round($totalScore / $subjectsCount, 2);

        StudentTermResult::query()->updateOrCreate(
            [
                'student_class_enrollment_id' => $enrollmentId,
                'term_id' => $termId,
            ],
            [
                'student_id' => $studentId,
                'school_class_id' => $schoolClassId,
                'academic_year_id' => $academicYearId,
                'subjects_count' => $subjectsCount,
                'total_score' => $totalScore,
                'average_score' => $averageScore,
                'calculated_at' => now(),
            ],
        );

        self::recalculateClassPositions($schoolClassId, $academicYearId, $termId);
    }

    protected static function recalculateClassPositions(
        string $schoolClassId,
        string $academicYearId,
        string $termId,
    ): void {
        $results = StudentTermResult::query()
            ->where('school_class_id', $schoolClassId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->with('student')
            ->get();

        $mandatorySubjectIds = ClassSubject::query()
            ->where('school_class_id', $schoolClassId)
            ->where('is_mandatory', true)
            ->pluck('subject_id');

        $eligibleResults = $results->filter(
            fn (StudentTermResult $result) => self::isEligibleForClassPosition(
                $result,
                $termId,
                $mandatorySubjectIds,
            ),
        );

        $rankedResults = $eligibleResults
            ->sort(function (StudentTermResult $first, StudentTermResult $second): int {
                $averageComparison = (float) $second->average_score <=> (float) $first->average_score;

                if ($averageComparison !== 0) {
                    return $averageComparison;
                }

                $totalComparison = (float) $second->total_score <=> (float) $first->total_score;

                if ($totalComparison !== 0) {
                    return $totalComparison;
                }

                return strcmp($first->student->admission_number, $second->student->admission_number);
            })
            ->values();

        $positionsByResultId = $rankedResults
            ->values()
            ->mapWithKeys(fn (StudentTermResult $result, int $index) => [
                $result->id => $index + 1,
            ]);

        foreach ($results as $result) {
            $classPosition = $positionsByResultId->get($result->id);

            if ($result->class_position !== $classPosition) {
                $result->update(['class_position' => $classPosition]);
            }
        }
    }

    /**
     * @param  Collection<int, string>  $mandatorySubjectIds
     */
    protected static function isEligibleForClassPosition(
        StudentTermResult $result,
        string $termId,
        $mandatorySubjectIds,
    ): bool {
        if ($mandatorySubjectIds->isEmpty()) {
            return $result->subjects_count > 0;
        }

        $markedSubjectIds = Mark::query()
            ->where('student_class_enrollment_id', $result->student_class_enrollment_id)
            ->where('term_id', $termId)
            ->pluck('subject_id');

        return $mandatorySubjectIds->every(
            fn (string $subjectId) => $markedSubjectIds->contains($subjectId),
        );
    }
}
