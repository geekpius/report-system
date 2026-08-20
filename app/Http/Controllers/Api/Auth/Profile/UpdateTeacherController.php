<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class UpdateTeacherController extends Controller
{
    /**
     * Update the authenticated teacher's profile.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher->update(snake_keys($request->validated()));

        return $this->success(
            TeacherResource::make($teacher),
            'Teacher updated successfully.',
        );
    }
}
