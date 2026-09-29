<?php

namespace App\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $meta
     * @param  array<string, string>  $headers
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success.',
        int $status = 200,
        ?array $meta = null,
        array $headers = [],
    ): JsonResponse {
        return $this->apiResponse($message, $data, $status, $meta, $headers);
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function error(string $message = 'Something went wrong.', int $status = 400, array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @param  array<string, string>  $headers
     */
    protected function apiResponse(
        string $message,
        mixed $data,
        int $status,
        ?array $meta = null,
        array $headers = [],
    ): JsonResponse {
        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, $headers);
    }
}
