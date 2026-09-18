<?php

namespace App\Actions\Api\School;

use App\Concerns\ApiResponse;
use App\Enums\SchoolStatus;
use App\Http\Requests\Api\School\StoreSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\Client;
use App\Models\MarkSetting;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreSchoolAction
{
    use ApiResponse;

    public function handle(StoreSchoolRequest $request, Client $client): JsonResponse
    {
        try {
            $school = DB::transaction(function () use ($request, $client): School {
                $payload = snake_keys($request->validated());
                $payload['status'] = SchoolStatus::Active;
                $payload['in_session'] = false;
                $payload['owner_id'] = $client->id;

                $school = School::query()->create($payload);

                MarkSetting::resolveForSchool($school);

                return $school;
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create school.');
        }

        return $this->success(
            SchoolResource::make($school),
            'School created successfully.',
            201,
        );
    }
}
