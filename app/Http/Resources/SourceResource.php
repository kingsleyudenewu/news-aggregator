<?php 
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->identifier,
            'name' => $this->name,
            'api_name' => $this->api_name,
            'description' => $this->description,
            'url' => $this->url,
            'language' => $this->language,
            'country' => $this->country,
            'categories' => $this->categories ? json_decode($this->categories) : [],
            'is_active' => (bool) $this->is_active,
            'last_fetched' => $this->last_fetched_at ? [
                'timestamp' => $this->last_fetched_at,
                'human' => \Carbon\Carbon::parse($this->last_fetched_at)->diffForHumans(),
            ] : null,
            'statistics' => $this->when($request->input('include_stats'), function () {
                return [
                    'total_articles' => $this->articles_count ?? 0,
                    'today_articles' => $this->today_articles_count ?? 0,
                    'week_articles' => $this->week_articles_count ?? 0,
                ];
            }),
            'status' => $this->getStatus(),
        ];
    }
    
    private function getStatus(): string
    {
        if (!$this->is_active) {
            return 'disabled';
        }
        
        if (!$this->last_fetched_at) {
            return 'pending';
        }
        
        $minutesSinceLastFetch = now()->diffInMinutes($this->last_fetched_at);
        
        if ($minutesSinceLastFetch < 60) {
            return 'healthy';
        } elseif ($minutesSinceLastFetch < 180) {
            return 'warning';
        }
        
        return 'error';
    }
    
    public function with(Request $request): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
        ];
    }
}