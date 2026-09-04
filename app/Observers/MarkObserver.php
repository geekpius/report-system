<?php

namespace App\Observers;

use App\Enums\ScoringMode;
use App\Models\Aggregate;
use App\Models\Mark;
use App\Models\MarkSetting;
use App\Models\School;
use App\Models\Student;
use App\Support\StudentTermResultCalculator;

class MarkObserver
{
    public function creating(Mark $mark): void
    {
        $this->touchScoreTimestamps($mark);
        $this->calculateScores($mark);
        $this->applyGrade($mark);
    }

    public function updating(Mark $mark): void
    {
        $this->touchScoreTimestamps($mark);
        $this->calculateScores($mark);
        $this->applyGrade($mark);
    }

    public function created(Mark $mark): void
    {
        if ((float) $mark->exam_score === 0.0 && $mark->participated) {
            return;
        }

        StudentTermResultCalculator::recalculateFromMark($mark);
    }

    public function updated(Mark $mark): void
    {
        StudentTermResultCalculator::recalculateFromMark($mark);
    }

    public function deleted(Mark $mark): void
    {
        StudentTermResultCalculator::recalculateFromMark($mark);
    }

    protected function touchScoreTimestamps(Mark $mark): void
    {
        if ($this->classScoreWasChanged($mark)) {
            $mark->class_score_updated_at = now();
        }

        if ($this->examScoreWasChanged($mark)) {
            $mark->exam_score_updated_at = now();
        }
    }

    protected function classScoreWasChanged(Mark $mark): bool
    {
        return $mark->isDirty('class_score')
            || $mark->isDirty('home_assignment_score')
            || $mark->isDirty('project_score')
            || $mark->isDirty('class_test_score');
    }

    protected function examScoreWasChanged(Mark $mark): bool
    {
        return $mark->isDirty('exam_score')
            || ($mark->exists && $mark->isDirty('participated'));
    }

    protected function calculateScores(Mark $mark): void
    {
        if (! $mark->participated) {
            $mark->exam_score = 0;
        }

        $setting = $this->resolveMarkSetting($mark);

        $continuousAssessmentScore = round(
            (float) $mark->class_score
            + (float) $mark->home_assignment_score
            + (float) $mark->project_score
            + (float) $mark->class_test_score,
            2,
        );

        if ($setting->scoring_mode === ScoringMode::TotalScore) {
            // This block handles score calculation for "TotalScore" scoring mode.
            // In this mode, only two input fields are relevant: class_score and exam_score.
            // The continuous assessment and exam contributions are set using the current values,
            // unless they are being actively changed (i.e., the model is "dirty" for those fields).
            // All the individual component scores (class, home assignment, project, class test, and total CA score)
            // are reset to zero.
            // The two relevant contributions are stored, and total_score is set as their sum.

            // Retrieve the class score input as a float
            $classScoreInput = (float) $mark->class_score;

            // If this is an update and the class_score field hasn't changed, reuse the existing contribution;
            // otherwise, calculate a new one by rounding classScoreInput.
            $continuousAssessmentContribution = $mark->exists && ! $mark->isDirty('class_score')
                ? (float) $mark->continuous_assessment_contribution
                : round($classScoreInput, 2);

            // If this is an update and the exam_score field hasn't changed, reuse the existing exam contribution;
            // otherwise, calculate a new one from exam_score.
            $examContribution = $mark->exists && ! $mark->isDirty('exam_score')
                ? (float) $mark->exam_contribution
                : round(((float) $mark->exam_score / 100) * (float) $setting->exam_score_percent, 2);

            // Set all the raw component fields and CA score to zero, since they are not used in this mode.
            $mark->class_score = 0;
            $mark->home_assignment_score = 0;
            $mark->project_score = 0;
            $mark->class_test_score = 0;
            $mark->continuous_assessment_score = 0;

            // Set the contributions and total score.
            $mark->continuous_assessment_contribution = $continuousAssessmentContribution;
            $mark->exam_contribution = $examContribution;
            $mark->total_score = round($continuousAssessmentContribution + $examContribution, 2);

            // No further calculation needed in this scoring mode.
            return;
        }

        $divisionTotal = (float) $setting->division_total;
        $continuousAssessmentContribution = $divisionTotal > 0
            ? round(($continuousAssessmentScore / $divisionTotal) * (float) $setting->division_total_percent, 2)
            : 0.0;
        $examContribution = round(((float) $mark->exam_score / 100) * (float) $setting->exam_allocation_percent, 2);

        $mark->continuous_assessment_score = $continuousAssessmentScore;
        $mark->continuous_assessment_contribution = $continuousAssessmentContribution;
        $mark->exam_contribution = $examContribution;
        $mark->total_score = round($continuousAssessmentContribution + $examContribution, 2);
    }

    protected function applyGrade(Mark $mark): void
    {
        if (! $mark->participated) {
            $mark->grade = null;
            $mark->grade_remark = null;

            return;
        }

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

    protected function resolveMarkSetting(Mark $mark): MarkSetting
    {
        $schoolId = $this->resolveSchoolId($mark);

        if ($schoolId === null) {
            return new MarkSetting(MarkSetting::defaults());
        }

        $school = School::query()->find($schoolId);

        if ($school === null) {
            return new MarkSetting(MarkSetting::defaults());
        }

        return MarkSetting::resolveForSchool($school);
    }

    protected function resolveSchoolId(Mark $mark): ?string
    {
        if (filled($mark->school_id)) {
            return $mark->school_id;
        }

        if ($mark->relationLoaded('student') && $mark->student !== null) {
            return $mark->student->school_id;
        }

        if ($mark->student_id === null) {
            return null;
        }

        return Student::query()->whereKey($mark->student_id)->value('school_id');
    }
}
