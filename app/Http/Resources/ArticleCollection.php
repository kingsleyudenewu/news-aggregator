<?php 

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleCollection extends ResourceCollection
{
    public $collects = ArticleResource::class;
    
    private array $additionalMeta = [];
    
    public function withMeta(array $meta): self
    {
        $this->additionalMeta = $meta;
        return $this;
    }
    
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => $this->getMeta(),
            'links' => $this->getLinks(),
            'filters' => $this->getAppliedFilters($request),
        ];
    }
    
    private function getMeta(): array
    {
        $meta = [];
        
        if ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $meta = [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ];
        } else {
            $meta = [
                'total' => $this->collection->count(),
            ];
        }
        
        return array_merge($meta, $this->additionalMeta, [
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    private function getLinks(): array
    {
        if (!($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
            return [];
        }
        
        return [
            'first' => $this->resource->url(1),
            'last' => $this->resource->url($this->resource->lastPage()),
            'prev' => $this->resource->previousPageUrl(),
            'next' => $this->resource->nextPageUrl(),
            'self' => $this->resource->url($this->resource->currentPage()),
        ];
    }
    
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        foreach (['category', 'source_name', 'author', 'published_after', 'published_before'] as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->input($filter);
            }
        }
        
        return $filters;
    }
    
    public function with(Request $request): array
    {
        return [
            'status' => 'success',
            'cached' => $request->header('X-Cache-Hit', false),
        ];
    }
}