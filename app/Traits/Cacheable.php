<?php 
namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    protected int $cacheTime = 3600; // 1 hour default
    
    protected function getCacheKey(string $method, ...$args): string
    {
        $class = class_basename($this);
        $argsHash = md5(serialize($args));
        
        return strtolower("{$class}_{$method}_{$argsHash}");
    }
    
    protected function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->cacheTime;
        
        return Cache::remember($key, $ttl, $callback);
    }
    
    protected function rememberForever(string $key, \Closure $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }
    
    protected function forget(string $key): bool
    {
        return Cache::forget($key);
    }
    
    protected function flush(string $pattern = '*'): void
    {
        Cache::flush();
    }
    
    protected function cacheMethod(string $method, array $args, \Closure $callback, ?int $ttl = null): mixed
    {
        $key = $this->getCacheKey($method, ...$args);
        
        return $this->remember($key, $callback, $ttl);
    }
    
    protected function bustMethodCache(string $method, ...$args): bool
    {
        $key = $this->getCacheKey($method, ...$args);
        
        return $this->forget($key);
    }
    
    protected function setCacheTime(int $seconds): self
    {
        $this->cacheTime = $seconds;
        
        return $this;
    }
    
    protected function withoutCache(\Closure $callback): mixed
    {
        $originalTime = $this->cacheTime;
        $this->cacheTime = 0;
        
        $result = $callback();
        
        $this->cacheTime = $originalTime;
        
        return $result;
    }
    
    protected function getCacheTags(): array
    {
        return [class_basename($this)];
    }
    
    protected function taggedCache(array $tags = []): \Illuminate\Cache\TaggedCache
    {
        $tags = array_merge($this->getCacheTags(), $tags);
        
        return Cache::tags($tags);
    }
}