<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_identifier',
        'preferred_sources',
        'preferred_categories',
        'preferred_authors',
        'excluded_keywords',
        'articles_per_page',
        'default_sort',
        'language',
        'timezone',
        'email_notifications',
        'notification_frequency',
        'last_notification_sent',
    ];
    
    protected $casts = [
        'preferred_sources' => 'array',
        'preferred_categories' => 'array',
        'preferred_authors' => 'array',
        'excluded_keywords' => 'array',
        'articles_per_page' => 'integer',
        'email_notifications' => 'boolean',
        'last_notification_sent' => 'datetime',
    ];
    
    protected $attributes = [
        'preferred_sources' => '[]',
        'preferred_categories' => '[]',
        'preferred_authors' => '[]',
        'excluded_keywords' => '[]',
        'articles_per_page' => 20,
        'default_sort' => 'published_at',
        'language' => 'en',
        'timezone' => 'UTC',
        'email_notifications' => false,
        'notification_frequency' => 'daily',
    ];
    
    // Scopes
    public function scopeByIdentifier($query, string $identifier)
    {
        return $query->where('user_identifier', $identifier);
    }
    
    public function scopeWithNotifications($query)
    {
        return $query->where('email_notifications', true);
    }
    
    public function scopeDueForNotification($query)
    {
        return $query->where('email_notifications', true)
            ->where(function ($q) {
                $q->whereNull('last_notification_sent')
                  ->orWhere(function ($q2) {
                      $q2->where('notification_frequency', 'hourly')
                         ->where('last_notification_sent', '<=', now()->subHour());
                  })
                  ->orWhere(function ($q2) {
                      $q2->where('notification_frequency', 'daily')
                         ->where('last_notification_sent', '<=', now()->subDay());
                  })
                  ->orWhere(function ($q2) {
                      $q2->where('notification_frequency', 'weekly')
                         ->where('last_notification_sent', '<=', now()->subWeek());
                  });
            });
    }
    
    // Accessors
    public function getHasPreferencesAttribute(): bool
    {
        return !empty($this->preferred_sources) ||
               !empty($this->preferred_categories) ||
               !empty($this->preferred_authors);
    }
    
    public function getPreferencesSummaryAttribute(): array
    {
        return [
            'sources' => count($this->preferred_sources ?? []),
            'categories' => count($this->preferred_categories ?? []),
            'authors' => count($this->preferred_authors ?? []),
            'excluded_keywords' => count($this->excluded_keywords ?? []),
        ];
    }
    
    // Methods
    public function addPreferredSource(string $source): void
    {
        $sources = $this->preferred_sources ?? [];
        if (!in_array($source, $sources)) {
            $sources[] = $source;
            $this->update(['preferred_sources' => $sources]);
        }
    }
    
    public function removePreferredSource(string $source): void
    {
        $sources = $this->preferred_sources ?? [];
        $sources = array_values(array_diff($sources, [$source]));
        $this->update(['preferred_sources' => $sources]);
    }
    
    public function addExcludedKeyword(string $keyword): void
    {
        $keywords = $this->excluded_keywords ?? [];
        if (!in_array($keyword, $keywords)) {
            $keywords[] = $keyword;
            $this->update(['excluded_keywords' => $keywords]);
        }
    }
    
    public function shouldExclude(string $text): bool
    {
        foreach ($this->excluded_keywords ?? [] as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    public function markNotificationSent(): void
    {
        $this->update(['last_notification_sent' => now()]);
    }
    
    public function resetPreferences(): void
    {
        $this->update([
            'preferred_sources' => [],
            'preferred_categories' => [],
            'preferred_authors' => [],
            'excluded_keywords' => [],
            'articles_per_page' => 20,
            'default_sort' => 'published_at',
        ]);
    }
}