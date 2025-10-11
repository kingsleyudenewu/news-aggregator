<?php 

namespace App\Exceptions;

use Exception;

class NewsSourceException extends Exception
{
    protected string $source;
    protected ?array $context;
    
    public function __construct(
        string $message, 
        string $source, 
        int $code = 0, 
        ?array $context = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->source = $source;
        $this->context = $context;
    }
    
    public function getSource(): string
    {
        return $this->source;
    }
    
    public function getContext(): ?array
    {
        return $this->context;
    }
    
    public static function connectionFailed(string $source, string $reason): self
    {
        return new self(
            "Failed to connect to {$source}: {$reason}",
            $source,
            500,
            ['reason' => $reason]
        );
    }
    
    public static function invalidResponse(string $source, string $details): self
    {
        return new self(
            "Invalid response from {$source}: {$details}",
            $source,
            422,
            ['details' => $details]
        );
    }
    
    public static function rateLimitExceeded(string $source, int $retryAfter = null): self
    {
        return new self(
            "Rate limit exceeded for {$source}",
            $source,
            429,
            ['retry_after' => $retryAfter]
        );
    }
    
    public static function authenticationFailed(string $source): self
    {
        return new self(
            "Authentication failed for {$source}",
            $source,
            401
        );
    }
    
    public static function sourceUnavailable(string $source): self
    {
        return new self(
            "News source {$source} is currently unavailable",
            $source,
            503
        );
    }
    
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => [
                    'message' => $this->getMessage(),
                    'source' => $this->source,
                    'code' => $this->getCode(),
                    'context' => $this->context
                ]
            ], $this->getCode() ?: 500);
        }
        
        return parent::render($request);
    }
}