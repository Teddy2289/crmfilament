<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RingoverRateLimit
{
    protected RateLimiter $limiter;

    protected int $maxAttempts = 2;

    protected int $decaySeconds = 1;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return $this->buildResponse($key);
        }

        $this->limiter->hit($key, $this->decaySeconds);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->limiter->retriesLeft($key, $this->maxAttempts));

        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $apiKey = $request->header('Authorization') 
            ?? $request->bearerToken() 
            ?? $request->input('api_key')
            ?? 'default';

        return sha1('ringover:'.$apiKey.':'.$request->ip());
    }

    protected function buildResponse(string $key): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Too many requests',
            'message' => 'Rate limit exceeded. Maximum 2 requests per second allowed.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }
}
