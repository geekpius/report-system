<?php

namespace App\Actions\Api\Term;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Term\StoreTermRequest;
use App\Http\Resources\TermResource;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreTermAction
{
    use ApiResponse;

    public function handle(StoreTermRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $validated = snake_keys($request->validated());

        try {
            $term = DB::transaction(function () use ($validated, $academicYear) {
                if (($validated['is_current'] ?? false) === true) {
                    Term::query()
                        ->where('academic_year_id', $academicYear->id)
                        ->update(['is_current' => false]);
                }

                return Term::query()->create([
                    'academic_year_id' => $academicYear->id,
                    ...$validated,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create term.');
        }

        return $this->success(
            TermResource::make($term->load('academicYear')),
            'Term created successfully.',
            201,
        );
    }
}
