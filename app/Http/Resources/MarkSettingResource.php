<?php

namespace App\Http\Resources;

use App\Models\MarkSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MarkSetting
 */
class MarkSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schoolId' => $this->school_id,
            'scoringMode' => $this->scoring_mode->value,
            'totalScore' => [
                'classScorePercent' => (float) $this->class_score_percent,
                'examScorePercent' => (float) $this->exam_score_percent,
            ],
            'divisionScore' => [
                'classScoreMax' => (float) $this->class_score_max,
                'homeAssignmentMax' => (float) $this->home_assignment_max,
                'projectMax' => (float) $this->project_max,
                'classTestMax' => (float) $this->class_test_max,
                'divisionTotal' => (float) $this->division_total,
                'divisionTotalPercent' => (float) $this->division_total_percent,
                'examAllocationPercent' => (float) $this->exam_allocation_percent,
            ],
        ];
    }
}
