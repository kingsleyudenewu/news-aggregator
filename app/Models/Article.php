<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Article extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'title',
        'description',
        'content',
        'author',
        'source_name',
        'source_id',
        'category',
        'url',
        'image_url',
        'published_at',
        'metadata',
        'content_hash',
        'view_count',
        'is_featured',
    ];
    
    protected $casts = [
        'published_at' => 'datetime',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
    ];
    
    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];
    
    // Relationships
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id', 'identifier');
    }
    
    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
    }
    
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
    
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
    
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source_name', $source);
    }
    
    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author', $author);
    }
    
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }
    
    public function scopePopular(Builder $query, int $threshold = 100): Builder
    {
        return $query->where('view_count', '>=', $threshold)
                    ->orderBy('view_count', 'desc');
    }
    
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->whereFullText(['title', 'description', 'content'], $term)
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
    
    // Accessors
    public function getExcerptAttribute(): string
    {
        if ($this->description) {
            return \Str::limit($this->description, 150);
        }
        
        if ($this->content) {
            return \Str::limit(strip_tags($this->content), 150);
        }
        
        return '';
    }
    
    public function getReadingTimeAttribute(): int
    {
        $content = $this->content ?? $this->description ?? '';
        $wordCount = str_word_count(strip_tags($content));
        
        // Average reading speed: 200 words per minute
        return max(1, ceil($wordCount / 200));
    }
    
    public function getFormattedPublishedDateAttribute(): string
    {
        return $this->published_at->format('M d, Y');
    }
    
    public function getIsNewAttribute(): bool
    {
        return $this->published_at->isAfter(now()->subHours(24));
    }
    
    // Methods
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }
    
    public function markAsFeatured(): void
    {
        $this->update(['is_featured' => true]);
    }
    
    public function unmarkAsFeatured(): void
    {
        $this->update(['is_featured' => false]);
    }
    
    public function hasImage(): bool
    {
        return !empty($this->image_url);
    }
    
    public function isFromSource(string $source): bool
    {
        return $this->source_name === $source || $this->source_id === $source;
    }
}