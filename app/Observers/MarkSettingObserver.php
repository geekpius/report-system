<?php

namespace App\Observers;

use App\Models\MarkSetting;

class MarkSettingObserver
{
    public function creating(MarkSetting $markSetting): void
    {
        $this->calculateDerivedFields($markSetting);
    }

    public function updating(MarkSetting $markSetting): void
    {
        $this->calculateDerivedFields($markSetting);
    }

    protected function calculateDerivedFields(MarkSetting $markSetting): void
    {
        $markSetting->division_total = round(
            (float) $markSetting->class_score_max
            + (float) $markSetting->home_assignment_max
            + (float) $markSetting->project_max
            + (float) $markSetting->class_test_max,
            2,
        );

        $markSetting->division_total_percent = round(100 - (float) $markSetting->exam_allocation_percent, 2);
    }
}
