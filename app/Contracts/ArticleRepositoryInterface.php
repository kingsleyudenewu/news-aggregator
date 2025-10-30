<?php 

namespace App\Contracts;

use App\DTOs\SearchCriteriaDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ArticleRepositoryInterface
{
    public function create(array $data): mixed;

    public function findById(int $id): mixed;

    public function search(SearchCriteriaDTO $searchCriteria): LengthAwarePaginator;

    public function getByFilters(array $filters, int $perPage=20): mixed;

    public function updateOrCreate(array $attributes, array $values): mixed;

    public function getLatestBySource(string $source, int $limit = 10): Collection;

    public function deleteOlderThan(\DateTime $dateTime): int;
    public function incrementViewCount(int $id): void;
    public function getPopularArticles(int $limit): Collection;
}