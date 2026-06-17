<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    private const MAX_BODY_LENGTH = 32768;

    private const REDACT_HEADERS = [
        'x-password',
        'x-token',
        'authorization',
        'cookie',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        try {
            $this->storeLog($request, $response, $startedAt);
        } catch (\Throwable) {
            // Jangan ganggu response API jika logging gagal
        }

        return $response;
    }

    private function storeLog(Request $request, Response $response, float $startedAt): void
    {
        $responseBody = $response->getContent();
        $responseCode = null;

        if ($responseBody && $this->isJson($responseBody)) {
            $decoded = json_decode($responseBody, true);
            $responseCode = $decoded['metadata']['code'] ?? null;
        }

        ApiRequestLog::create([
            'method' => $request->method(),
            'endpoint' => '/'.$request->path(),
            'full_url' => $request->fullUrl(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'client_username' => $request->header('x-username'),
            'request_headers' => $this->sanitizeHeaders($request),
            'request_body' => $this->truncate($this->formatBody($request->getContent())),
            'response_status' => $response->getStatusCode(),
            'response_code' => $responseCode,
            'response_body' => $this->truncate($responseBody ?: null),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    private function sanitizeHeaders(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            $lower = strtolower($key);
            if (in_array($lower, self::REDACT_HEADERS, true)) {
                $headers[$key] = ['***REDACTED***'];
                continue;
            }

            $headers[$key] = $values;
        }

        return $headers;
    }

    private function formatBody(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if ($this->isJson($body)) {
            return json_encode(json_decode($body, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return $body;
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) <= self::MAX_BODY_LENGTH) {
            return $value;
        }

        return substr($value, 0, self::MAX_BODY_LENGTH)."\n... [truncated]";
    }

    private function isJson(string $value): bool
    {
        if (! str_starts_with(trim($value), '{') && ! str_starts_with(trim($value), '[')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
