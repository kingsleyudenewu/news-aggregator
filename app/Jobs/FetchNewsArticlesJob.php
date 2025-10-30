<?php

namespace App\Jobs;

use App\Contracts\NewsSourceInterface;
use App\Services\NewsAdapters\GuardianApiAdapter;
use App\Services\NewsAdapters\NewsApiAdapter;
use App\Services\NewsAdapters\NytApiAdapter;
use App\Services\NewsAggregator\AggregatorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchNewsArticlesJob implements ShouldQueue
{
    use Queueable;

    private array $availableSources = [
        'newsapi' => NewsApiAdapter::class,
        'guardian' => GuardianApiAdapter::class,
        'nyt' => NytApiAdapter::class
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(public string $source)
    {}

    /**
     * Execute the job.
     */
    public function handle(AggregatorService $aggregatorService): void
    {
        try {
            $this->source === 'all' ? 
            $this->fetchAllSources($aggregatorService) : 
            $this->fetchFromSource($this->source, $aggregatorService);
            
            Log::info('News fetch completed', ['results' => $results ?? []]);
            
        } catch (Exception $e) {
            Log::error('News fetch failed', ['error' => $e->getMessage()]);
            
        }
    }

    private function fetchAllSources(AggregatorService $aggregatorService)
    {
        $results = $aggregatorService->fetchFromAllSources();

        foreach ($results['sources'] as $sourceName => $sourceResults) {
            if (isset($sourceResults['error'])) {
                Log::error("Failed to fetch from {$sourceName}: {$sourceResults['error']}");
            } else {
                Log::info("Fetched from {$sourceName}: {$sourceResults['saved']} saved, {$sourceResults['duplicates']} duplicates");
            }
        }

        Log::info("Total articles saved: {$results['success']}");
        Log::info("Total duplicates: {$results['duplicates']}");
    }

    private function fetchFromSource(string $source, AggregatorService $aggregatorService)
    {
        if (!array_key_exists($source, $this->availableSources)) {
            Log::error("Source {$source} is not supported.");
            return;
        }

        $adapterClass = $this->availableSources[$source];
        $adapter = $this->createAdapterInstance($adapterClass);

        $results = $aggregatorService->fetchFromSource($adapter);

        if (isset($results['error'])) {
            Log::error("Failed to fetch from {$source}: {$results['error']}");
        } else {
            Log::info("Fetched from {$source}: {$results['saved']} saved, {$results['duplicates']} duplicates");
        }
    }

    private function createAdapterInstance(string $adapterClass)
    {
        $adapter = app($adapterClass);

        if (!$adapter instanceof NewsSourceInterface) {
            throw new Exception("Adapter class {$adapterClass} must implement NewsSourceInterface.");
        }

        return $adapter;
    }
}
