<?php 
namespace App\Services\NewsAggregator;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\NewsSourceInterface;
use App\DTOs\ArticleDTO;
use App\Services\Cache\ArticleCacheService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregatorService
{
    private Collection $adapters;
    private ArticleRepositoryInterface $repository;
    private ArticleCacheService $cache;
    private DuplicationChecker $duplicationChecker;
    private ArticleProcessor $processor;
    
    public function __construct(
        ArticleRepositoryInterface $repository,
        ArticleCacheService $cache,
        DuplicationChecker $duplicationChecker,
        ArticleProcessor $processor
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->duplicationChecker = $duplicationChecker;
        $this->processor = $processor;
        $this->adapters = collect();
    }
    
    public function registerAdapter(NewsSourceInterface $adapter): self
    {
        $this->adapters->put($adapter->getSourceName(), $adapter);
        return $this;
    }
    
    public function fetchFromAllSources(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'sources' => []
        ];
        
        foreach ($this->adapters as $name => $adapter) {
            try {
                $sourceResults = $this->fetchFromSource($adapter);
                $results['sources'][$name] = $sourceResults;
                $results['success'] += $sourceResults['saved'];
                $results['duplicates'] += $sourceResults['duplicates'];
                
            } catch (Exception $e) {
                Log::error("Failed to fetch from {$name}", [
                    'error' => $e->getMessage()
                ]);
                $results['failed']++;
                $results['sources'][$name] = ['error' => $e->getMessage()];
            }
        }
        
        // Clean up old articles
        $this->cleanupOldArticles();
        
        // Clear cache
        $this->cache->flush();
        
        return $results;
    }
    
    public function fetchFromSource(NewsSourceInterface $adapter): array
    {
        $articles = $adapter->fetchArticles();
        
        return $this->processArticles($articles, $adapter->getSourceName());
    }
    
    public function searchAcrossSources(string $query, array $filters = []): Collection
    {
        $allArticles = collect();
        
        $sources = $filters['sources'] ?? $this->adapters->keys()->toArray();
        
        foreach ($sources as $sourceName) {
            if (!$this->adapters->has($sourceName)) {
                continue;
            }
            
            $adapter = $this->adapters->get($sourceName);
            
            try {
                $articles = $adapter->searchArticles($query, $filters);
                $allArticles = $allArticles->merge($articles);
            } catch (Exception $e) {
                Log::error("Search failed for {$sourceName}", [
                    'error' => $e->getMessage(),
                    'query' => $query
                ]);
            }
        }
        
        // Remove duplicates and process
        $uniqueArticles = $this->duplicationChecker->removeDuplicates($allArticles);
        
        // Save to database
        $this->saveArticles($uniqueArticles);
        
        return $uniqueArticles;
    }
    
    protected function processArticles(Collection $articles, string $source): array
    {
        $saved = 0;
        $duplicates = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($articles as $articleDto) {
                // Process article (clean, validate, enrich)
                $processedArticle = $this->processor->process($articleDto);
                
                // Check for duplicates
                if ($this->duplicationChecker->isDuplicate($processedArticle)) {
                    $duplicates++;
                    continue;
                }
                
                // Save to database
                $this->repository->updateOrCreate(
                    ['content_hash' => $processedArticle->toArray()['content_hash']],
                    $processedArticle->toArray()
                );
                
                $saved++;
            }
            
            DB::commit();
            
            // Update source last fetched timestamp
            DB::table('sources')
                ->where('name', $source)
                ->update(['last_fetched_at' => now()]);
                
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'total' => $articles->count(),
            'saved' => $saved,
            'duplicates' => $duplicates
        ];
    }
    
    protected function saveArticles(Collection $articles): void
    {
        foreach ($articles->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $article) {
                    $this->repository->updateOrCreate(
                        ['content_hash' => $article->toArray()['content_hash']],
                        $article->toArray()
                    );
                }
            });
        }
    }
    
    protected function cleanupOldArticles(): int
    {
        $daysToKeep = config('news.days_to_keep', 30);
        $cutoffDate = now()->subDays($daysToKeep);
        
        return $this->repository->deleteOlderThan($cutoffDate);
    }
    
    public function getAvailableSources(): Collection
    {
        return $this->adapters->map(function ($adapter, $name) {
            return [
                'name' => $name,
                'available' => $adapter->isAvailable(),
                'categories' => $adapter->getCategories()
            ];
        });
    }
}