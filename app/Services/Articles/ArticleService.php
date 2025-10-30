<?php 

namespace App\Services\Articles;

use App\Contracts\ArticleRepositoryInterface;
use App\DTOs\SearchCriteriaDTO;
use App\Http\Requests\FilterArticlesRequest;
use App\Http\Requests\SearchArticlesRequest;
use App\Services\Cache\ArticleCacheService;
use App\Services\NewsAggregator\AggregatorService;
use Illuminate\Http\Request;

class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $repository,
        private AggregatorService $aggregator,
        private ArticleCacheService $cache
    ) {}

    public function fetchLatestArticles(FilterArticlesRequest $request): mixed
    {
        $cacheKey = 'articles_' . md5(json_encode($request->validated()));

        return $this->cache->remember($cacheKey, 600, function () use ($request) {
            return $this->repository->getByFilters(
                $request->validated(),
                $request->input('per_page', 20)
            );
        });
    }

    public function searchArticles(SearchArticlesRequest $request): mixed
    {
        $criteria = SearchCriteriaDTO::fromRequest($request->validated());

        // Search in database first
        $articles = $this->repository->search($criteria);
        
        // If results are limited, search live sources
        if ($articles->total() < 10 && $request->input('live_search', false) === true) {
            $liveArticles = $this->aggregator->searchAcrossSources(
                $criteria->q,
                $request->validated()
            );
            
            // Re-search database after new articles are saved
            $articles = $this->repository->search($criteria);
        }

        return $articles;
    }

    public function filterByCategory(Request $request, string $category)
    {
        return $this->repository->getByFilters(
            ['category' => $category],
            $request->input('per_page', 20)
        );
    }

    public function fetchPopularArticles(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $articles = $this->cache->remember("popular_{$limit}", 1800, function () use ($limit) {
            return $this->repository->getPopularArticles($limit);
        });

        return $articles;
    }
}