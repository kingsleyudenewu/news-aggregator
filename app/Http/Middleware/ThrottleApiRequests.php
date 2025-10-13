<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiRequests
{
    protected RateLimiter $limiter;
    
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }
    
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildException($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts)
        );
    }
    
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->method() . 
            '|' . $request->path() . 
            '|' . $request->ip()
        );
    }
    
    protected function getMaxAttempts(Request $request): int
    {
        // Different limits for different endpoints
        if (str_contains($request->path(), 'search')) {
            return 30; // 30 searches per minute
        }
        
        return 60; // 60 requests per minute default
    }
    
    protected function buildException(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);
        
        return response()->json([
            'message' => 'Too Many Attempts.',
            'retry_after' => $retryAfter
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }
    
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        // return $response->withHeaders([
        //     'X-RateLimit-Limit' => $maxAttempts,
        //     'X-RateLimit-Remaining' => $remainingAttempts,
        // ]);

        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
        return $response;
    }
}