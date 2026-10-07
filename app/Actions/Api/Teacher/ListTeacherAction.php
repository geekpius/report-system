<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Http\Requests\Api\Teacher\ListTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class ListTeacherAction
{
    use ApiResponse;

    public function handle(ListTeacherRequest $request, School $school): JsonResponse
    {
        $searchTerm = $request->string('searchTerm')->trim()->toString();

        $teachers = paginate(
            $school->teachers()
                ->with('client')
                ->when($searchTerm !== '', function ($query) use ($searchTerm): void {
                    $term = '%'.$searchTerm.'%';

                    $query->where(function ($query) use ($term): void {
                        $query->where('staff_number', 'like', $term)
                            ->orWhereHas('client', function ($query) use ($term): void {
                                $query->where('name', 'like', $term)
                                    ->orWhere('email', 'like', $term);
                            });
                    });
                })
                ->orderBy('staff_number')
        );

        return $this->success(
            TeacherResource::collection($teachers),
            'Teachers retrieved successfully.',
            meta: pagination_meta($teachers),
        );
    }
}
