<?php 

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GuardianApiAdapter extends BaseNewsAdapter
{
    protected string $sourceName = 'guardian';
    public function __construct()
    {
        $this->apiKey = config('services.guardian.key');
        $this->baseUrl = config('services.guardian.url', 'https://content.guardianapis.com');
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
            $fields = $article['fields'] ?? [];

            return new ArticleDTO(
                title: $article['webTitle'] ?? '',
                description: $fields['trailText'] ?? null,
                content: $fields['bodyText'] ?? $fields['body'] ?? null,
                author: $fields['byline'] ?? null,
                sourceName: $this->sourceName,
                sourceId: 'guardian',
                category: $article['sectionName'] ?? null,
                url: $article['webUrl'],
                imageUrl: $fields['thumbnail'] ?? null,
                publishedAt: Carbon::parse($article['webPublicationDate'] ?? null),
                externalId: $article['id'] ?? null,
                metadata: [
                    'section_id' => $article['sectionId'] ?? null,
                    'type' => $article['type'] ?? null,
                    'tags' => $article['tags'] ?? [],
                ],
            );
        })->filter(fn($article) => $article !== null);
    }

    public function fetchArticles(array $params = []): Collection
    {
        $defaultParams = [
            'api-key' => $this->apiKey,
            'show-fields' => 'all',
            'page-size' => $params['page_size'] ?? 50,
            'order-by' => 'newest',
        ];

        $params = array_merge($defaultParams, $params);
        $url = $this->buildRequestUrl('search', $params);
        
        $response = $this->makeRequest($url, $params);

        if(!$response || !isset($response['response']['results'])) {
            return collect();
        }

        return $this->parseArticles($response['response']['results']);
    }

    public function searchArticles(string $query, array $filters = []): Collection
    {
        $endpoint = 'search';
        $params = array_merge(['q' => $query, 'show-fields' => 'all', 'page-size' => $filters['pageSize'] ?? 50, 'api-key' => $this->apiKey], $filters);
        $url = $this->buildRequestUrl($endpoint, $params);
        $response = $this->makeRequest($url, $params);

        if(!$response || !isset($response['response']['results'])) {
            return collect();
        }
        return $this->parseArticles($response['response']['results']);
    }

    public function getCategories(): array
    {
        return ['world', 'uk-news', 'politics', 'sport', 'football', 'business', 'technology', 'science', 'environment', 'culture', 'lifeandstyle'];
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }
}