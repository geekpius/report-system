<?php

namespace App\Actions\Api\MarkSetting;

use App\Concerns\ApiResponse;
use App\Http\Resources\MarkSettingResource;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ShowMarkSettingAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        return $this->success(
            MarkSettingResource::make(MarkSetting::resolveForSchool($school)),
            'Mark settings retrieved successfully.',
        );
    }
}
