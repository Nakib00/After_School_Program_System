<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 * Standardizes API responses for consistent structure and reusability.
 */
trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function success($data = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $code
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function error(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => 'Error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError($errors, string $message = 'Validation Error'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }
}
