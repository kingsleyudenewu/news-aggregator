<?php 

namespace App\DTOs;

use Illuminate\Support\Collection;

class UserPreferencesDTO
{
    public function __construct(
        public readonly string $userIdentifier,
        public readonly array $preferredSources = [],
        public readonly array $preferredCategories = [],
        public readonly array $preferredAuthors = [],
        public readonly array $excludedKeywords = [],
        public readonly int $articlesPerPage = 20,
        public readonly string $defaultSort = 'published_at',
        public readonly ?string $language = 'en',
        public readonly ?string $timezone = 'UTC'
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            userIdentifier: $data['user_identifier'] ?? session()->getId(),
            preferredSources: $data['preferred_sources'] ?? [],
            preferredCategories: $data['preferred_categories'] ?? [],
            preferredAuthors: $data['preferred_authors'] ?? [],
            excludedKeywords: $data['excluded_keywords'] ?? [],
            articlesPerPage: $data['articles_per_page'] ?? 20,
            defaultSort: $data['default_sort'] ?? 'published_at',
            language: $data['language'] ?? 'en',
            timezone: $data['timezone'] ?? 'UTC'
        );
    }
    
    public static function fromRequest(array $validated): self
    {
        return new self(
            userIdentifier: $validated['user_identifier'] ?? session()->getId(),
            preferredSources: $validated['sources'] ?? [],
            preferredCategories: $validated['categories'] ?? [],
            preferredAuthors: $validated['authors'] ?? [],
            excludedKeywords: $validated['exclude'] ?? [],
            articlesPerPage: $validated['per_page'] ?? 20,
            defaultSort: $validated['sort'] ?? 'published_at',
            language: $validated['language'] ?? 'en',
            timezone: $validated['timezone'] ?? 'UTC'
        );
    }
    
    public function toArray(): array
    {
        return [
            'user_identifier' => $this->userIdentifier,
            'preferred_sources' => $this->preferredSources,
            'preferred_categories' => $this->preferredCategories,
            'preferred_authors' => $this->preferredAuthors,
            'excluded_keywords' => $this->excludedKeywords,
            'articles_per_page' => $this->articlesPerPage,
            'default_sort' => $this->defaultSort,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];
    }
    
    public function hasPreferences(): bool
    {
        return !empty($this->preferredSources) || 
               !empty($this->preferredCategories) || 
               !empty($this->preferredAuthors);
    }
    
    public function shouldExclude(string $text): bool
    {
        foreach ($this->excludedKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}