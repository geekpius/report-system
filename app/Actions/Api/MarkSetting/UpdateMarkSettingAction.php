<?php

namespace App\Actions\Api\MarkSetting;

use App\Concerns\ApiResponse;
use App\Enums\ScoringMode;
use App\Http\Requests\Api\MarkSetting\UpdateMarkSettingRequest;
use App\Http\Resources\MarkSettingResource;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Throwable;

class UpdateMarkSettingAction
{
    use ApiResponse;

    public function handle(UpdateMarkSettingRequest $request, School $school): JsonResponse
    {
        $setting = $school->markSetting;

        if ($setting === null) {
            $setting = $school->markSetting()->create(MarkSetting::defaults());
        }

        $scoringMode = ScoringMode::from($request->string('scoringMode')->toString());

        $attributes = [
            'scoring_mode' => $scoringMode,
        ];

        if ($scoringMode === ScoringMode::TotalScore) {
            $attributes['class_score_percent'] = $request->input('totalScore.classScorePercent');
            $attributes['exam_score_percent'] = $request->input('totalScore.examScorePercent');
        }

        if ($scoringMode === ScoringMode::DivisionScore) {
            $attributes['class_score_max'] = $request->input('divisionScore.classScoreMax');
            $attributes['home_assignment_max'] = $request->input('divisionScore.homeAssignmentMax');
            $attributes['project_max'] = $request->input('divisionScore.projectMax');
            $attributes['class_test_max'] = $request->input('divisionScore.classTestMax');
            $attributes['exam_allocation_percent'] = $request->input('divisionScore.examAllocationPercent');
        }

        try {
            $setting->update($attributes);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update mark settings.');
        }

        return $this->success(
            MarkSettingResource::make($setting->refresh()),
            'Mark settings updated successfully.',
        );
    }
}
