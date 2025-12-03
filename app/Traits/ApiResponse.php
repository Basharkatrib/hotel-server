<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param array|string $messages
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, $messages = [], $code = 200)
    {
        return response()->json([
            'status' => true,
            'data' => $data,
            'messages' => is_array($messages) ? $messages : [$messages],
            'code' => $code,
        ], $code);
    }

    /**
     * Error response
     *
     * @param array|string $messages
     * @param int $code
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($messages = [], $code = 400, $data = null)
    {
        return response()->json([
            'status' => false,
            'data' => $data,
            'messages' => is_array($messages) ? $messages : [$messages],
            'code' => $code,
        ], $code);
    }
}

