<?php

namespace App\Actions\Api\ClassSubject;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\ClassSubject\StoreClassSubjectRequest;
use App\Http\Resources\ClassSubjectResource;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreClassSubjectAction
{
    use ApiResponse;

    public function handle(StoreClassSubjectRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        try {
            $classSubjects = DB::transaction(function () use ($request, $schoolClass) {
                return collect($request->validated('subjects'))
                    ->map(fn (array $subject) => ClassSubject::query()->create([
                        'school_class_id' => $schoolClass->id,
                        'subject_id' => $subject['subjectId'],
                        'is_mandatory' => $subject['isMandatory'] ?? true,
                    ]))
                    ->each(fn (ClassSubject $classSubject) => $classSubject->load(['schoolClass', 'subject']))
                    ->values();
            });
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to assign subjects to class.');
        }

        return $this->success(
            ClassSubjectResource::collection($classSubjects),
            'Subjects assigned to class successfully.',
            201,
        );
    }
}
