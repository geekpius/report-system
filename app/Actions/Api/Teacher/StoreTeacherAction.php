<?php

namespace App\Actions\Api\Teacher;

use App\Concerns\ApiResponse;
use App\Enums\ClientStatus;
use App\Enums\Role;
use App\Http\Requests\Api\Teacher\StoreTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Client;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class StoreTeacherAction
{
    use ApiResponse;

    public function handle(StoreTeacherRequest $request, School $school): JsonResponse
    {
        try {
            $teacher = DB::transaction(function () use ($request, $school): Teacher {
                $client = Client::query()->create([
                    'name' => $request->validated('name'),
                    'email' => $request->validated('email'),
                    'password' => Str::password(32),
                    'role' => Role::Teacher,
                    'status' => ClientStatus::Active,
                ]);

                return Teacher::query()->create([
                    'client_id' => $client->id,
                    'school_id' => $school->id,
                    'staff_number' => $request->validated('staffNumber'),
                    'phone' => $request->validated('phone'),
                ]);
            });

            Password::broker('clients')->sendResetLink([
                'email' => $teacher->client->email,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->error('Unable to create teacher.');
        }

        return $this->success(
            TeacherResource::make($teacher->load('client')),
            'Teacher created successfully.',
            201,
        );
    }
}
