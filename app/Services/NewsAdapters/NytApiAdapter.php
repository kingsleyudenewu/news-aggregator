<?php 

namespace App\Services\NewsAdapters;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NytApiAdapter extends BaseNewsAdapter
{
    protected string $sourceName = 'The New York Times';

    public function __construct()
    {
        $this->apiKey = config('services.nyt.key');
        $this->baseUrl = config('services.nyt.url', 'https://api.nytimes.com/svc');
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
            $multimedia = collect($article['multimedia'] ?? []);
            $image = $multimedia->firstWhere('format', 'mediumThreeByTwo440');

            $fields = $article['fields'] ?? [];

            return new ArticleDTO(
                title: $article['title'] ?? 'Untitled',
                description: $fields['abstract'] ?? null,
                content: null,
                author: $article['byline'] ?? null,
                sourceName: $this->sourceName,
                sourceId: 'nyt',
                category: $article['section'] ?? null,
                url: $article['url'] ?? $article['short_url'] ?? '',
                imageUrl: $image ? $image['url'] : null,
                publishedAt: Carbon::parse($article['published_date'] ?? $article['created_date']),
                externalId: $article['uri'] ?? null,
                metadata: [
                    'section' => $article['section'] ?? null,
                    'subsection' => $article['subsection'] ?? null,
                    'material_type' => $article['material_type_facet'] ?? null,
                ],
            );
        })->filter(fn($article) => $article !== null);
    }

    public function fetchArticles(array $articles = []): Collection
    {
        // Example: fetch top stories
        $endpoint = 'topstories/v2/home.json';
        $url = $this->buildRequestUrl($endpoint, []);
        $response = $this->makeRequest($url);
        return $this->parseArticles($response);
    }

    public function getCategories(): array
    {
        return [
            'arts',
            'automobiles',
            'books',
            'business',
            'fashion',
            'food',
            'health',
            'home',
            'insider',
            'opinion',
            'magazine',
            'movies',
            'world',
            'politics',
            'business',
            'technology',
            'science',
            'health',
            'sports',
            'obituaries',
            'fashion',
            'travel',
            'realestate',
            'sundayreview',
            'upshot',
            'theater',
            'us',
            'world',
        ];
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function searchArticles(string $query, array $filters = []): Collection
    {
        $endpoint = 'search/v2/articlesearch.json';
        $params = array_merge(['q' => $query, 'sort' => $filters['sort'] ?? 'newest', 'page' => $filters['page'] ?? 0], $filters);

        if (isset($filters['begin_date'])) {
            $params['begin_date'] = Carbon::parse($filters['begin_date'])->format('Ymd');
        }

        if (isset($filters['end_date'])) {
            $params['end_date'] = Carbon::parse($filters['end_date'])->format('Ymd');
        }

        $url = $this->buildRequestUrl($endpoint, $params);
        $response = $this->makeRequest($url);

        if (!$response || !isset($response['response']['docs'])) {
            return collect();
        }
        
        $response = $response['response']['docs'];
        return $this->parseSearchResults($response);
    }

    protected function parseSearchResults(array $docs) 
    {
        return collect($docs)->map(function ($doc) {
            $multimedia = collect($doc['multimedia'] ?? []);
            $image = $multimedia->first();

            return new ArticleDTO(
                title: $doc['headline']['main'] ?? 'Untitled',
                description: $doc['abstract'] ?? $doc['lead_paragraph'] ?? null,
                content: $doc['lead_paragraph'] ?? null,
                author: $doc['byline']['original'] ?? null,
                sourceName: $this->sourceName,
                sourceId: 'nyt',
                category: $doc['section_name'] ?? null,
                url: $doc['web_url'] ?? '',
                imageUrl: $image ? 'https://www.nytimes.com/' . $image['url'] : null,
                publishedAt: Carbon::parse($doc['pub_date']),
                externalId: $doc['_id'] ?? null,
                metadata: [
                    'document_type' => $doc['document_type'] ?? null,
                    'news_desk' => $doc['news_desk'] ?? null,
                    'word_count' => $doc['word_count'] ?? 0,
                ],
            );
        })->filter(fn($article) => $article !== null);
    }
}