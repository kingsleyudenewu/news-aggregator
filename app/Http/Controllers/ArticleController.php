<?php 
namespace App\Http\Controllers;

use App\Contracts\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterArticlesRequest;
use App\Http\Requests\SearchArticlesRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Services\Articles\ArticleService;
use App\Services\Cache\ArticleCacheService;
use App\Services\NewsAggregator\AggregatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    private ArticleRepositoryInterface $repository;
    private AggregatorService $aggregator;
    private ArticleCacheService $cache;
    
    public function __construct(
        ArticleRepositoryInterface $repository,
        AggregatorService $aggregator,
        ArticleCacheService $cache,
        private ArticleService $articleService
    ) {
        $this->repository = $repository;
        $this->aggregator = $aggregator;
        $this->cache = $cache;
    }
    
    /**
     * Get paginated list of articles
     */
    public function index(FilterArticlesRequest $request): ArticleCollection
    {
        $articles = $this->articleService->fetchLatestArticles($request);
        
        return new ArticleCollection($articles);
    }
    
    /**
     * Search articles across all sources
     */
    public function search(SearchArticlesRequest $request): JsonResponse
    {
        $articles = $this->articleService->searchArticles($request);
        
        return response()->json([
            'data' => ArticleResource::collection($articles->items()),
            'meta' => [
                'total' => $articles->total(),
                'per_page' => $articles->perPage(),
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'from' => $articles->firstItem(),
                'to' => $articles->lastItem(),
            ],
            'links' => [
                'first' => $articles->url(1),
                'last' => $articles->url($articles->lastPage()),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
        ]);
    }
    
    /**
     * Get single article
     */
    public function show(int $id): JsonResponse
    {
        $article = $this->repository->findById($id);
        
        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }
        
        // Increment view count
        $this->repository->incrementViewCount($id);
        
        return response()->json([
            'data' => new ArticleResource($article)
        ]);
    }
    
    /**
     * Get articles by category
     */
    public function byCategory(Request $request, string $category): ArticleCollection
    {
        $articles = $this->articleService->filterByCategory($request, $category);
        
        return new ArticleCollection($articles);
    }
    
    /**
     * Get popular articles
     */
    public function popular(Request $request): JsonResponse
    {        
        $articles = $this->articleService->fetchPopularArticles($request);
        
        return response()->json([
            'data' => ArticleResource::collection($articles)
        ]);
    }
    
    /**
     * Get latest articles by source
     */
    public function bySource(string $source, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        
        $articles = $this->repository->getLatestBySource($source, $limit);
        
        return response()->json([
            'data' => ArticleResource::collection($articles)
        ]);
    }
    
    /**
     * Refresh articles from all sources (admin only)
     */
    public function refresh(): JsonResponse
    {
        $results = $this->aggregator->fetchFromAllSources();
        
        return response()->json([
            'message' => 'Articles refreshed successfully',
            'data' => $results
        ]);
    }
}