<?php

namespace App\Helpers;

class ResponseFormatter
{
    public static function success($data = null, $message = null, $code = 200)
    {
        return self::json($data, $message, $code);
    }

    public static function error($data = null, $message = null, $code = 400)
    {
        return self::json($data, $message, $code);
    }

    private static function json($data, $message, int $code)
    {
        return response()->json([
            'metadata' => [
                'code' => $code,
                'message' => $message,
            ],
            'response' => $data,
        ], $code);
    }
}
