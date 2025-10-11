<?php 

namespace App\Contracts;

use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    /**
     * Summary of fetchArticles
     * @param array $articles
     * @return Collection
     */
    public function fetchArticles(array $articles = []): Collection;

    /**
     * Summary of searchArticles
     * @param string $query
     * @param array $filters
     * @return Collection
     */
    public function searchArticles(string $query, array $filters = []): Collection;

    /**
     * Summary of getCategories
     * @return array
     */
    public function getCategories(): array;

    /**
     * Summary of getSourceName
     * @return string
     */
    public function getSourceName() : string;

    /**
     * Summary of isAvailable
     * @return bool
     */
    public function isAvailable(): bool;
}
