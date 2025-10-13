<?php 

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Services\Cache\ArticleCacheService;
use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class ArticleCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private ArticleCacheService $service;


    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArticleCacheService();
        Cache::flush();
    }
    
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function it_can_remember_a_value(): void
    {
        $key = 'test_article';
        $value = ['id' => 1, 'title' => 'Test Article'];
        $ttl = 3600;
        
        $result = $this->service->remember($key, $ttl, function () use ($value) {
            return $value;
        });
        
        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has($this->service->getFullKey($key)));
    }

    #[Test]
    public function it_returns_cached_value_on_subsequent_remember_calls(): void
    {
        $key = 'article_data';
        $firstValue = ['id' => 1];
        $secondValue = ['id' => 2];
        
        $first = $this->service->remember($key, 3600, function () use ($firstValue) {
            return $firstValue;
        });
        
        $this->assertEquals($firstValue, $first);
        
        $second = $this->service->remember($key, 3600, function () use ($secondValue) {
            return $secondValue;
        });
        
        $this->assertEquals($firstValue, $second);
    }

    #[Test]
    public function it_can_get_a_cached_value(): void
    {
        $key = 'articles_list';
        $value = [['id' => 1], ['id' => 2]];
        
        Cache::put($this->service->getFullKey($key), $value, 600);
        $result = $this->service->get($key);
        
        $this->assertEquals($value, $result);
    }
    
    #[Test]
    public function it_returns_null_for_non_existent_key(): void
    {
        $result = $this->service->get('non_existent_key');
        
        $this->assertNull($result);
    }

    #[Test]
    public function it_uses_default_ttl_when_ttl_is_zero(): void
    {
        $key = 'article_default_ttl';
        $value = 'cached article';
        
        $this->service->put($key, $value, 0);
        
        $this->assertEquals($value, $this->service->get($key));
    }
}