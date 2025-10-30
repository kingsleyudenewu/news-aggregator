<?php 

namespace App\Repositories;

use App\Contracts\ArticleRepositoryInterface;
use App\DTOs\SearchCriteriaDTO;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ArticleRepository implements ArticleRepositoryInterface
{
    private Article $model;
    
    public function __construct(Article $model)
    {
        $this->model = $model;
    }
    
    public function create(array $data): Article
    {
        return $this->model->create($data);
    }
    
    public function findById(int $id): ?Article
    {
        return Cache::remember("article_{$id}", 3600, function () use ($id) {
            return $this->model->find($id);
        });
    }
    
    public function search(SearchCriteriaDTO $criteria): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        
        // Full-text search
        if ($criteria->q) {
            $query->whereFullText(['title', 'description', 'content'], $criteria->q);
        }
        
        // Filter by sources
        if ($criteria->sources) {
            $query->whereIn('source_name', $criteria->sources);
        }
        
        // Filter by categories
        if ($criteria->categories) {
            $query->whereIn('category', $criteria->categories);
        }
        
        // Filter by authors
        if ($criteria->authors) {
            $query->whereIn('author', $criteria->authors);
        }
        
        // Date range filter
        if ($criteria->dateFrom) {
            $query->where('published_at', '>=', $criteria->dateFrom);
        }
        
        if ($criteria->dateTo) {
            $query->where('published_at', '<=', $criteria->dateTo);
        }
        
        // Apply sorting
        $query->orderBy($criteria->sortBy, $criteria->sortOrder);
        
        return $query->paginate($criteria->perPage);
    }
    
    public function getByFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        $queryFilters = collect($filters)->only([
            'source_name', 'source_id', 'category', 'author', 'is_featured'
        ])->filter()->toArray();
        
        foreach ($queryFilters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->latest($filters['sort_by'] ?? 'published_at')->paginate($filters['per_page'] ?? $perPage);
    }
    
    public function updateOrCreate(array $attributes, array $values): Article
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
    
    public function getLatestBySource(string $source, int $limit = 10): Collection
    {
        return Cache::remember("latest_{$source}_{$limit}", 600, function () use ($source, $limit) {
            return $this->model
                ->where('source_name', $source)
                ->latest('published_at')
                ->limit($limit)
                ->get();
        });
    }
    
    public function deleteOlderThan(\DateTime $date): int
    {
        return $this->model
            ->where('published_at', '<', $date)
            ->where('is_featured', false)
            ->delete();
    }
    
    public function getPopularArticles(int $limit = 10): Collection
    {
        return Cache::remember("popular_articles_{$limit}", 1800, function () use ($limit) {
            return $this->model
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    public function incrementViewCount(int $id): void
    {
        $this->model->where('id', $id)->increment('view_count');
        Cache::forget("article_{$id}");
    }
}