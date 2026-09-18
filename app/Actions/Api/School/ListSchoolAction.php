<?php

namespace App\Actions\Api\School;

use App\Concerns\ApiResponse;
use App\Http\Resources\SchoolResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ListSchoolAction
{
    use ApiResponse;

    public function handle(Client $client): JsonResponse
    {
        $schools = $client->schools()
            ->orderBy('name')
            ->get();

        return $this->success(
            SchoolResource::collection($schools),
            'Schools retrieved successfully.',
        );
    }
}
