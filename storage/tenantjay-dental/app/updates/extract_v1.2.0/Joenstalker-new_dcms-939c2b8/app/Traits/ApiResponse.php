<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function respondSuccess(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'code' => $status,
            'data' => $data,
        ], $status);
    }

    protected function respondError(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $status,
            'data' => $data,
        ], $status);
    }
}
