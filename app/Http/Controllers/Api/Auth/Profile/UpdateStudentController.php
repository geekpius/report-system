<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class UpdateStudentController extends Controller
{
    /**
     * Update the authenticated student's profile.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update(snake_keys($request->validated()));

        return $this->success(
            StudentResource::make($student),
            'Student updated successfully.',
        );
    }
}
