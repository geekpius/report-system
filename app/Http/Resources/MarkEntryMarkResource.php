<?php

namespace App\Http\Resources;

use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Mark
 */
class MarkEntryMarkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participated' => $this->participated,
            'classScore' => (float) $this->class_score,
            'homeAssignmentScore' => (float) $this->home_assignment_score,
            'projectScore' => (float) $this->project_score,
            'classTestScore' => (float) $this->class_test_score,
            'continuousAssessmentScore' => (float) $this->continuous_assessment_score,
            'continuousAssessmentContribution' => (float) $this->continuous_assessment_contribution,
            'examScore' => (float) $this->exam_score,
            'examContribution' => (float) $this->exam_contribution,
            'totalScore' => (float) $this->total_score,
            'classScoreUpdatedAt' => $this->class_score_updated_at?->toIso8601String(),
            'examScoreUpdatedAt' => $this->exam_score_updated_at?->toIso8601String(),
            'closeClassScoreEntry' => $this->close_class_score_entry,
            'closeExamScoreEntry' => $this->close_exam_score_entry,
            'grade' => $this->grade,
            'gradeRemark' => $this->grade_remark,
            'teacherId' => $this->teacher_id,
        ];
    }
}
