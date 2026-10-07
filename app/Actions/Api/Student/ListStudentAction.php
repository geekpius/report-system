<?php

namespace App\Actions\Api\Student;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Student\ListStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListStudentAction
{
    use ApiResponse;

    public function handle(ListStudentRequest $request, School $school): JsonResponse
    {
        $searchTerm = $request->string('searchTerm')->trim()->toString();

        $students = paginate(
            $school->students()
                ->with('schoolClass')
                ->when($searchTerm !== '', function ($query) use ($searchTerm): void {
                    $term = '%'.$searchTerm.'%';

                    $query->where(function ($query) use ($term): void {
                        $query->where('admission_number', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('middle_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    });
                })
                ->orderBy('admission_number')
        );

        return $this->success(
            StudentResource::collection($students),
            'Students retrieved successfully.',
            meta: pagination_meta($students),
        );
    }
}
