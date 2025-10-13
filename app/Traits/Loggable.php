<?php 

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait Loggable
{
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel($this->getLogChannel())->info($this->formatMessage($message), $context);
    }
    
    protected function logError(string $message, array $context = []): void
    {
        Log::channel($this->getLogChannel())->error($this->formatMessage($message), $context);
    }
    
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel($this->getLogChannel())->warning($this->formatMessage($message), $context);
    }
    
    protected function getLogChannel(): string
    {
        return property_exists($this, 'logChannel') 
            ? $this->logChannel 
            : 'daily';
    }
    
    protected function formatMessage(string $message): string
    {
        $class = class_basename($this);
        return "[{$class}] {$message}";
    }
}