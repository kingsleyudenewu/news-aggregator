<?php 

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NewsApiAdapter extends BaseNewsAdapter
{
    protected string $sourceName = 'NewsAPI';
    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key');
        $this->baseUrl = config('services.newsapi.url', 'https://newsapi.org/v2/');
    }

    public function buildRequestUrl(string $endpoint, array $params): string
    {
        $params['apiKey'] = $this->apiKey;
        $query = http_build_query($params);
        return rtrim($this->baseUrl, '/') . '/' . $endpoint . '?' . $query;
    }

    public function parseArticles(array $articles): Collection
    {
        return collect($articles)->map(function ($article) {
            return new ArticleDTO(
                title: $article['title'] ?? '',
                description: $article['description'] ?? null,
                content: $article['content'] ?? null,
                author: $article['author'] ?? null,
                sourceName: $this->sourceName,
                sourceId: $article['source']['id'] ?? $article['source']['name'] ?? 'unknown',
                category: $this->extractCategory($article),
                url: $article['url'],
                imageUrl: $article['urlToImage'] ?? null,
                publishedAt: Carbon::parse($article['publishedAt'] ?? null),
                externalId: md5($article['url']),
                metadata: [
                    'source_details' => $article['source'] ?? [],
                ],
            );
        })->filter(fn($article) => $article !== null);
    }

    private function extractCategory(array $article): ?string
    {
        $url = $article['url'] ?? '';
        $categories = $this->getCategories();

        foreach ($categories as $category) {
            if(strpos($url,$category) !== false) {
                return $category;
            }
        }
        
        return 'general';
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function fetchArticles(array $params = []): Collection
    {
        $url = $this->buildRequestUrl('top-headlines', $params);
        $response = $this->makeRequest($url);

        if(!$response || empty(data_get($response, 'articles', []))) {
            return collect();
        }

        return $this->parseArticles($response['articles'] ?? []);
    }

    public function getCategories(): array
    {
        return ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'];
    }

    public function searchArticles(string $query, array $filters = []): Collection
    {
        $endpoint = 'everything';
        $params = array_merge($filters, [
            'q' => $query,
            'sortBy' => $filters['sortBy'] ?? 'publishedAt',
            'pageSize' => $filters['perPage'] ?? 100,
        ]);
        $url = $this->buildRequestUrl($endpoint, $params);
        $response = $this->makeRequest($url);

        if(!$response || empty(data_get($response, 'articles', []))) {
            return collect();
        }

        return $this->parseArticles($response['articles'] ?? []);
    }
}