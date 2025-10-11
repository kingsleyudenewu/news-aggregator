<?php 

namespace App\DTOs;

use Illuminate\Support\Carbon;

class SearchCriteriaDTO
{
    public function __construct(
        public readonly ?string $query = null,
        public readonly ?array $sources = null,
        public readonly ?array $categories = null,
        public readonly ?array $authors = null,
        public readonly ?Carbon $dateFrom = null,
        public readonly ?Carbon $dateTo = null,
        public readonly ?string $sortBy = 'published_at',
        public readonly ?string $sortOrder = 'desc',
        public readonly int $perPage = 20,
        public readonly ?array $excludedKeywords = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            query: $data['query'] ?? null,
            sources: $data['sources'] ?? null,
            categories: $data['categories'] ?? null,
            authors: $data['authors'] ?? null,
            dateFrom: isset($data['date_from']) ? Carbon::parse($data['date_from']) : null,
            dateTo: isset($data['date_to']) ? Carbon::parse($data['date_to']) : null,
            sortBy: $data['sort_by'] ?? 'published_at',
            sortOrder: $data['sort_order'] ?? 'desc',
            perPage: $data['per_page'] ?? 20,
            excludedKeywords: $data['excluded_keywords'] ?? null,
        );
    }
}