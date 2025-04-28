<?php 

namespace App\Services;

class ApiResponseService
{
    /**
     * Send a successful response.
     *
     * @param mixed $result
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result
        ], $statusCode);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param array $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($message, $errors = [], $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $errors
        ], $statusCode);
    }
}

