<?php 
namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ArticleCacheService
{
    private string $prefix = 'news_aggregator:';
    private int $defaultTtl;
    
    public function __construct()
    {
        $this->defaultTtl = config('news.cache_ttl', 600);
    }
    
    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        $fullKey = $this->prefix . $key;
        
        return Cache::remember($fullKey, $ttl, $callback);
    }
    
    public function get(string $key): mixed
    {
        return Cache::get($this->prefix . $key);
    }
    
    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $ttl = ($ttl > 0) ? $ttl : $this->defaultTtl;
        Cache::put($this->prefix . $key, $value, $ttl);
    }
    
    public function forget(string $key): void
    {
        Cache::forget($this->prefix . $key);
    }
    
    public function flush(): void
    {
        // Flush only news aggregator cache
        $pattern = $this->prefix . '*';
        
        Cache::flush();
    }
    
    public function tags(array $tags): self
    {
        Cache::tags($tags);
        return $this;
    }

    public function getFullKey(string $key): string
{
    return $this->prefix . $key;
}
}