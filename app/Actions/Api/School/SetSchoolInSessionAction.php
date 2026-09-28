<?php

namespace App\Actions\Api\School;

use App\Concerns\ApiResponse;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetSchoolInSessionAction
{
    use ApiResponse;

    public function handle(School $school): JsonResponse
    {
        try {
            DB::transaction(function () use ($school): void {
                School::query()
                    ->where('owner_id', $school->owner_id)
                    ->whereKeyNot($school->id)
                    ->where('in_session', true)
                    ->update(['in_session' => false]);

                $school->update(['in_session' => true]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to set school in session.');
        }

        return $this->success(
            SchoolResource::make($school->fresh()),
            'School is now in session.',
        );
    }
}
