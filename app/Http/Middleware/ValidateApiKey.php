<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ValidateApiKey
{
    private const HEADER_NAME = 'X-API-Key';
    private const QUERY_PARAM = 'api_key';
    
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }
        
        if (!$this->isValidApiKey($apiKey, $scope)) {
            Log::warning('Invalid API key attempt', [
                'key' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            
            return $this->unauthorizedResponse('Invalid API key');
        }
        
        // Track API key usage
        $this->trackUsage($apiKey, $request);
        
        // Add API key info to request
        $request->merge(['api_key_id' => $this->getApiKeyId($apiKey)]);
        
        return $next($request);
    }
    
    private function extractApiKey(Request $request): ?string
    {
        // Check header first
        if ($request->hasHeader(self::HEADER_NAME)) {
            return $request->header(self::HEADER_NAME);
        }
        
        // Check query parameter as fallback
        if ($request->has(self::QUERY_PARAM)) {
            return $request->input(self::QUERY_PARAM);
        }
        
        // Check bearer token
        if ($request->bearerToken()) {
            return $request->bearerToken();
        }
        
        return null;
    }
    
    private function isValidApiKey(string $apiKey, ?string $scope): bool
    {
        // Cache API key validation for performance
        return Cache::remember("api_key_valid_{$apiKey}", 300, function () use ($apiKey, $scope) {
            // In production, check against database
            // For MVP, check against config
            $validKeys = config('api.keys', []);
            
            if (!isset($validKeys[$apiKey])) {
                return false;
            }
            
            $keyConfig = $validKeys[$apiKey];
            
            // Check if key is active
            if (!($keyConfig['active'] ?? true)) {
                return false;
            }
            
            // Check scope if provided
            if ($scope && !in_array($scope, $keyConfig['scopes'] ?? [])) {
                return false;
            }
            
            // Check expiration
            if (isset($keyConfig['expires_at'])) {
                $expiresAt = \Carbon\Carbon::parse($keyConfig['expires_at']);
                if ($expiresAt->isPast()) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    private function trackUsage(string $apiKey, Request $request): void
    {
        $key = "api_usage_{$apiKey}_" . now()->format('Y-m-d');
        
        Cache::increment($key);
        
        // Log high usage
        $usage = Cache::get($key, 0);
        if ($usage > 1000 && $usage % 1000 === 0) {
            Log::info('High API usage detected', [
                'key' => substr($apiKey, 0, 8) . '...',
                'usage' => $usage,
                'date' => now()->format('Y-m-d')
            ]);
        }
    }
    
    private function getApiKeyId(string $apiKey): string
    {
        $validKeys = config('api.keys', []);
        return $validKeys[$apiKey]['id'] ?? 'unknown';
    }
    
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => [
                'message' => $message,
                'code' => 'UNAUTHORIZED',
                'status' => 401
            ]
        ], 401);
    }
}