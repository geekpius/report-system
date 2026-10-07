<?php

namespace App\Actions\Api\ClassSubject;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\ClassSubject\ListClassSubjectRequest;
use App\Http\Resources\ClassSubjectResource;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;

class ListClassSubjectAction
{
    use ApiResponse;

    public function handle(ListClassSubjectRequest $request, SchoolClass $schoolClass): JsonResponse
    {
        $classSubjects = $schoolClass->classSubjects()
            ->with('subject')
            ->when($request->has('isMandatory'), function ($query) use ($request): void {
                $query->where('is_mandatory', $request->boolean('isMandatory'));
            })
            ->get()
            ->sortBy([
                ['is_mandatory', 'desc'],
                ['subject.name', 'asc'],
            ])
            ->values();

        return $this->success(
            ClassSubjectResource::collection($classSubjects),
            'Class subjects retrieved successfully.',
        );
    }
}
