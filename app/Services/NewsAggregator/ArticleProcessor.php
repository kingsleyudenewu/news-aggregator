<?php 
namespace App\Services\NewsAggregator;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Str;

class ArticleProcessor
{
    public function process(ArticleDTO $article): ArticleDTO
    {
        // Clean and validate data
        $cleanedData = [
            'title' => $this->cleanTitle($article->title),
            'description' => $this->cleanDescription($article->description),
            'content' => $this->cleanContent($article->content),
            'author' => $this->normalizeAuthor($article->author),
            'source_name' => $article->sourceName,
            'source_id' => $article->sourceId,
            'category' => $this->normalizeCategory($article->category),
            'url' => $this->validateUrl($article->url),
            'image_url' => $this->validateImageUrl($article->imageUrl),
            'published_at' => $article->publishedAt,
            'external_id' => $article->externalId,
            'metadata' => $this->enrichMetadata($article->metadata),
        ];
        
        return ArticleDTO::fromArray($cleanedData);
    }
    
    private function cleanTitle(string $title): string
    {
        // Remove excessive whitespace and special characters
        $title = preg_replace('/\s+/', ' ', trim($title));
        $title = strip_tags($title);
        
        // Ensure minimum length
        if (strlen($title) < 10) {
            $title .= ' - Article';
        }
        
        return Str::limit($title, 255);
    }
    
    private function cleanDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }
        
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', trim($description));
        
        return Str::limit($description, 500);
    }
    
    private function cleanContent(?string $content): ?string
    {
        if (!$content) {
            return null;
        }
        
        // Remove scripts and styles
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
        $content = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $content);
        
        // Convert to plain text but keep paragraph structure
        $content = strip_tags($content, '<p><br>');
        
        return $content;
    }
    
    private function normalizeAuthor(?string $author): ?string
    {
        if (!$author) {
            return null;
        }
        
        // Clean author name
        $author = trim($author);
        $author = preg_replace('/^by\s+/i', '', $author);
        
        return Str::limit($author, 100);
    }
    
    private function normalizeCategory(?string $category): ?string
    {
        if (!$category) {
            return 'general';
        }
        
        // Normalize category names
        $category = strtolower(trim($category));
        $category = str_replace([' ', '-', '_'], '', $category);
        
        // Map to standard categories
        $categoryMap = [
            'tech' => 'technology',
            'sports' => 'sport',
            'politics' => 'politics',
            'biz' => 'business',
            'entertainment' => 'entertainment',
            'science' => 'science',
            'health' => 'health',
        ];
        
        return $categoryMap[$category] ?? $category;
    }
    
    private function validateUrl(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }
        
        return $url;
    }
    
    private function validateImageUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Ensure HTTPS for images
        return str_replace('http://', 'https://', $url);
    }
    
    private function enrichMetadata(array $metadata): array
    {
        // Add processing metadata
        $metadata['processed_at'] = now()->toIso8601String();
        $metadata['processor_version'] = '1.0';
        
        return $metadata;
    }
}