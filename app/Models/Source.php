<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Source extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'identifier',
        'api_name',
        'url',
        'description',
        'language',
        'country',
        'is_active',
        'categories',
        'api_config',
        'last_fetched_at',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'categories' => 'array',
        'api_config' => 'encrypted:array',
        'last_fetched_at' => 'datetime',
    ];
    
    protected $dates = [
        'last_fetched_at',
        'created_at',
        'updated_at',
    ];
    
    // Relationships
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'source_id', 'identifier');
    }
    
    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
    
    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }
    
    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }
    
    public function scopeRecentlyFetched(Builder $query, int $hours = 1): Builder
    {
        return $query->where('last_fetched_at', '>=', now()->subHours($hours));
    }
    
    public function scopeStale(Builder $query, int $hours = 2): Builder
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_fetched_at')
              ->orWhere('last_fetched_at', '<', now()->subHours($hours));
        });
    }
    
    // Accessors
    public function getIsHealthyAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if (!$this->last_fetched_at) {
            return false;
        }
        
        return $this->last_fetched_at->isAfter(now()->subHours(2));
    }
    
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->last_fetched_at) {
            return 'never_fetched';
        }
        
        $hoursSinceLastFetch = $this->last_fetched_at->diffInHours(now());
        
        if ($hoursSinceLastFetch < 1) {
            return 'healthy';
        } elseif ($hoursSinceLastFetch < 3) {
            return 'warning';
        }
        
        return 'error';
    }
    
    public function getArticleCountAttribute(): int
    {
        return $this->articles()->count();
    }
    
    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
    
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
    
    public function markAsFetched(): void
    {
        $this->update(['last_fetched_at' => now()]);
    }
    
    public function needsFetch(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if (!$this->last_fetched_at) {
            return true;
        }
        
        $fetchInterval = config('news.fetch_interval', 30);
        
        return $this->last_fetched_at->addMinutes($fetchInterval)->isPast();
    }
    
    public function hasCategory(string $category): bool
    {
        return in_array($category, $this->categories ?? []);
    }
    
    public function updateApiConfig(array $config): void
    {
        $this->update(['api_config' => array_merge($this->api_config ?? [], $config)]);
    }
}