<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Teacher\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateTeacherAction
{
    use ApiResponse;

    public function handle(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $teacher): void {
                $teacher->client->update([
                    'name' => $request->validated('name'),
                ]);

                $teacher->update([
                    'phone' => $request->validated('phone'),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to update teacher.');
        }

        return $this->success(
            TeacherResource::make($teacher),
            'Teacher updated successfully.',
        );
    }
}
