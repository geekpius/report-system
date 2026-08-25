<?php

namespace App\Observers;

use App\Models\Aggregate;
use App\Models\Mark;
use App\Models\Student;
use App\Support\StudentTermResultCalculator;

class MarkObserver
{
    public function creating(Mark $mark): void
    {
        $this->calculateScores($mark);
        $this->applyGrade($mark);
    }

    public function updating(Mark $mark): void
    {
        $this->calculateScores($mark);
        $this->applyGrade($mark);
    }

    public function saved(Mark $mark): void
    {
        StudentTermResultCalculator::recalculateFromMark($mark);
    }

    public function deleted(Mark $mark): void
    {
        StudentTermResultCalculator::recalculateFromMark($mark);
    }

    protected function calculateScores(Mark $mark): void
    {
        $continuousAssessmentScore = round(
            (float) $mark->class_score
            + (float) $mark->home_assignment_score
            + (float) $mark->project_score
            + (float) $mark->class_test_score,
            2,
        );

        $continuousAssessmentContribution = round(($continuousAssessmentScore / 60) * 50, 2);
        $examContribution = round(((float) $mark->exam_score / 100) * 50, 2);
        $totalScore = round($continuousAssessmentContribution + $examContribution, 2);

        $mark->continuous_assessment_score = $continuousAssessmentScore;
        $mark->continuous_assessment_contribution = $continuousAssessmentContribution;
        $mark->exam_contribution = $examContribution;
        $mark->total_score = $totalScore;
    }

    protected function applyGrade(Mark $mark): void
    {
        $schoolId = $this->resolveSchoolId($mark);

        if ($schoolId === null) {
            $mark->grade = null;
            $mark->grade_remark = null;

            return;
        }

        $aggregate = Aggregate::findForScore((float) $mark->total_score, $schoolId);

        $mark->grade = $aggregate?->grade;
        $mark->grade_remark = $aggregate?->remarks;
    }

    protected function resolveSchoolId(Mark $mark): ?string
    {
        if ($mark->relationLoaded('student') && $mark->student !== null) {
            return $mark->student->school_id;
        }

        if ($mark->student_id === null) {
            return null;
        }

        return Student::query()->whereKey($mark->student_id)->value('school_id');
    }
}
