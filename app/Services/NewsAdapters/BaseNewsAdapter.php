<?php 

namespace App\Services\NewsAdapters;

use App\Contracts\NewsSourceInterface;
use App\Traits\Loggable;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

abstract class BaseNewsAdapter implements NewsSourceInterface
{
    use Loggable;

    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout =  30; // seconds
    protected int $retryTimes = 3;
    protected int $retryDelay = 100; 

    abstract public function parseArticles(array $response): Collection;
    abstract public function buildRequestUrl(string $endpoint, array $params): string;

    protected function makeRequest(string $url, array $params = []): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            } 

            $this->logError("API request failed: ", [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (Exception $e) {
            $this->logError("Request exception: " . $e->getMessage());
            return [];
        }
    }

    public function isAvailable(): bool
    {
        try {
            $testUrl = $this->buildRequestUrl('test', []);
            $response = Http::timeout(5)->head($testUrl);
            return $response->successful();
        } catch (Exception $e) {
            $this->logError("Health check failed: " . $e->getMessage());
            return false;
        }
    }
}