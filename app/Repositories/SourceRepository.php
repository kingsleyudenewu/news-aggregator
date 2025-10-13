<?php 
namespace App\Repositories;

use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SourceRepository
{
    private Source $model;
    
    public function __construct(Source $model)
    {
        $this->model = $model;
    }
    
    public function all(): Collection
    {
        return Cache::remember('all_sources', 3600, function () {
            return $this->model->orderBy('name')->get();
        });
    }
    
    public function active(): Collection
    {
        return Cache::remember('active_sources', 600, function () {
            return $this->model->active()->orderBy('name')->get();
        });
    }
    
    public function findByIdentifier(string $identifier): ?Source
    {
        return $this->model->where('identifier', $identifier)->first();
    }
    
    public function findByApiName(string $apiName): ?Source
    {
        return $this->model->where('api_name', $apiName)->first();
    }
    
    public function getStale(int $hours = 2): Collection
    {
        return $this->model->active()->stale($hours)->get();
    }
    
    public function markAsFetched(string $identifier): bool
    {
        return $this->model->where('identifier', $identifier)
            ->update(['last_fetched_at' => now()]) > 0;
    }
    
    public function updateStatus(string $identifier, bool $active): bool
    {
        $updated = $this->model->where('identifier', $identifier)
            ->update(['is_active' => $active]) > 0;
            
        if ($updated) {
            Cache::forget('all_sources');
            Cache::forget('active_sources');
        }
        
        return $updated;
    }
    
    public function getStatistics(): array
    {
        return Cache::remember('source_statistics', 1800, function () {
            return [
                'total' => $this->model->count(),
                'active' => $this->model->active()->count(),
                'healthy' => $this->model->active()->recentlyFetched(1)->count(),
                'warning' => $this->model->active()->stale(1)->recentlyFetched(3)->count(),
                'error' => $this->model->active()->stale(3)->count(),
            ];
        });
    }
    
    public function getByLanguage(string $language): Collection
    {
        return $this->model->active()
            ->where('language', $language)
            ->orderBy('name')
            ->get();
    }
    
    public function getCategories(): array
    {
        return Cache::remember('source_categories', 3600, function () {
            $categories = [];
            
            $this->model->active()->get()->each(function ($source) use (&$categories) {
                if ($source->categories) {
                    $categories = array_merge($categories, $source->categories);
                }
            });
            
            return array_unique($categories);
        });
    }
}