<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use Closure;
use Exception;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $headerParser = new \PHPOpenSourceSaver\JWTAuth\Http\Parser\AuthHeaders;
        $headerParser->setHeaderName('x-token');
        JWTAuth::parser()->setChain([$headerParser]);

        $token = $request->header('x-token');
        $username = $request->header('x-username');

        if (!$token || !$username) {
            return ResponseFormatter::error([], 'Credentials must be provided', 201);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if ($user->username != $username) {
                return ResponseFormatter::error([], 'Invalid credentials', 201);
            }
        } catch (Exception $e) {
            if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                return ResponseFormatter::error([], 'Token Invalid', 201);
            } elseif ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                return ResponseFormatter::error([], 'Token Expired', 201);
            } elseif ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException) {
                return ResponseFormatter::error([], 'Token Blacklisted', 201);
            } else {
                return ResponseFormatter::error([], 'Authorization Token not found', 201);
            }
        }

        return $next($request);
    }
}
