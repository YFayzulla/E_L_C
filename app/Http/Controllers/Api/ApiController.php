<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Shared JSON envelope for the mobile API.
 *
 * Every response has the same shape so the Flutter client can decode it with
 * one model:
 *
 *   { "ok": true,  "data": ... }
 *   { "ok": false, "message": "...", "errors": {...} }
 */
abstract class ApiController extends Controller
{
    protected function ok($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['ok' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        return response()->json($payload, $status);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['ok' => false, 'message' => $message];

        if ($errors) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
