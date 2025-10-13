<?php 
namespace App\Console\Commands;

use App\Contracts\NewsSourceInterface;
use App\Services\NewsAdapters\GuardianApiAdapter;
use App\Services\NewsAdapters\NewsApiAdapter;
use App\Services\NewsAdapters\NytApiAdapter;
use App\Services\NewsAggregator\AggregatorService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNewsArticles extends Command
{
    protected $signature = 'news:fetch {--source=all : Specific source to fetch from}';
    protected $description = 'Fetch latest news articles from configured sources';
    
    private AggregatorService $aggregator;

    private array $availableSources = [
        'newsapi' => NewsApiAdapter::class,
        'guardian' =>   GuardianApiAdapter::class,
        'nyt' => NytApiAdapter::class
    ];
    
    public function __construct(AggregatorService $aggregator)
    {
        parent::__construct();
        $this->aggregator = $aggregator;
    }
    
    public function handle(): int
    {
        $this->info('Starting news article fetch...');
        
        $source = $this->option('source');
        
        try {
            if ($source === 'all') {
                $this->fetchAllSources();
            } else {
                $this->info("Fetching from {$source}...");
                $this->fetchFromSource($source);
            }
            
            Log::info('News fetch completed', ['results' => $results ?? []]);

            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('Failed to fetch articles: ' . $e->getMessage());
            Log::error('News fetch failed', ['error' => $e->getMessage()]);
            
            return Command::FAILURE;
        }
    }

    private function fetchAllSources()
    {
        $results = $this->aggregator->fetchFromAllSources();
                
        foreach ($results['sources'] as $sourceName => $sourceResults) {
            if (isset($sourceResults['error'])) {
                $this->error("Failed to fetch from {$sourceName}: {$sourceResults['error']}");
            } else {
                $this->info("Fetched from {$sourceName}: {$sourceResults['saved']} saved, {$sourceResults['duplicates']} duplicates");
            }
        }
        
        $this->info("Total articles saved: {$results['success']}");
        $this->info("Total duplicates: {$results['duplicates']}");
    }

    private function fetchFromSource(string $source)
    {
        if (!array_key_exists($source, $this->availableSources)) {
            $this->error("Source {$source} is not supported.");
            return;
        }

        $adapterClass = $this->availableSources[$source];
        $adapter = $this->createAdapterInstance($adapterClass);

        $results = $this->aggregator->fetchFromSource($adapter);

        if (isset($results['error'])) {
            $this->error("Failed to fetch from {$source}: {$results['error']}");
        } else {
            $this->info("Fetched from {$source}: {$results['saved']} saved, {$results['duplicates']} duplicates");
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