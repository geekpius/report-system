<?php

namespace App\Actions\Api\MarkSetting;

use App\Concerns\ApiResponse;
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
        $setting = MarkSetting::resolveForSchool($school);

        try {
            $setting->update([
                'scoring_mode' => $request->input('scoringMode'),
                'class_score_percent' => $request->input('totalScore.classScorePercent'),
                'exam_score_percent' => $request->input('totalScore.examScorePercent'),
                'class_score_max' => $request->input('divisionScore.classScoreMax'),
                'home_assignment_max' => $request->input('divisionScore.homeAssignmentMax'),
                'project_max' => $request->input('divisionScore.projectMax'),
                'class_test_max' => $request->input('divisionScore.classTestMax'),
                'exam_allocation_percent' => $request->input('divisionScore.examAllocationPercent'),
            ]);
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
