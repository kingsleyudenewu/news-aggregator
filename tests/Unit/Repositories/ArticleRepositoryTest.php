<?php 

namespace Tests\Unit\Repositories;

use App\DTOs\SearchCriteriaDTO;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use App\Services\Cache\ArticleCacheService;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ArticleCacheService $service;

    private ArticleRepository $repository;

    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArticleCacheService();
        $this->repository = new ArticleRepository(new Article());
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Article::query()->delete();
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_an_article(): void
    {
        $data = [
            'title' => 'Test Article',
            'description' => 'Test description',
            'content' => 'Test content',
            'source_name' => 'BBC',
            'source_id' => '12345',
            'author' => 'John Doe',
            'category' => 'Technology',
            'published_at' => now(),
            'content_hash' => 'hash123',
            'url' => 'https://example.com/test-article',
        ];
        
        $article = $this->repository->create($data);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => $article->title,
        ]);
        
        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals('BBC', $article->source_name);
        $this->assertTrue($article->exists);
    }

    #[Test]
    public function it_can_find_an_article_by_id(): void
    {
        $article = Article::factory()->create();
        Cache::flush();
        
        $found = $this->repository->findById($article->id);

        $this->assertInstanceOf(Article::class, $found);
        $this->assertEquals($article->id, $found->id);
        $this->assertEquals($article->title, $found->title);
    }

    #[Test]
    public function it_can_search_articles_by_full_text(): void
    {
        $this->withoutExceptionHandling();
        Article::factory()->create([
            'title' => 'Laravel Caching Strategies',
            'description' => 'Learn about caching',
            'content' => 'Cache implementation guide',
        ]);
        
        Article::factory()->create([
            'title' => 'Python Data Science',
            'description' => 'Data analysis tools',
            'content' => 'NumPy and Pandas',
        ]);
        
        $criteria = new SearchCriteriaDTO(
            'Laravel',
            ['newsapi'],
            [],
            [],
            null,
            null,
            null,
            'desc',
            10,
            null
        );
        
        $results = $this->repository->search($criteria);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
    }
}