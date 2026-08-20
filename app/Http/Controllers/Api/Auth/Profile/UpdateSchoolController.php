<?php

namespace App\Http\Controllers\Api\Auth\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\Profile\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class UpdateSchoolController extends Controller
{
    /**
     * Update the authenticated owner's school.
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $school->update(snake_keys($request->validated()));

        return $this->success(
            SchoolResource::make($school),
            'School updated successfully.',
        );
    }
}
