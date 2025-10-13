<?php 
namespace App\Providers;

use App\Contracts\ArticleRepositoryInterface;
use App\Repositories\ArticleRepository;
use App\Services\NewsAdapters\GuardianApiAdapter;
use App\Services\NewsAdapters\NewsApiAdapter;
use App\Services\NewsAdapters\NytApiAdapter;
use App\Services\NewsAggregator\AggregatorService;
use Illuminate\Support\ServiceProvider;

class NewsAggregatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        
        // Register aggregator service as singleton
        $this->app->singleton(AggregatorService::class, function ($app) {
            $aggregator = new AggregatorService(
                $app->make(ArticleRepositoryInterface::class),
                $app->make(\App\Services\Cache\ArticleCacheService::class),
                $app->make(\App\Services\NewsAggregator\DuplicationChecker::class),
                $app->make(\App\Services\NewsAggregator\ArticleProcessor::class)
            );
            
            // Register adapters
            if (config('services.newsapi.key')) {
                $aggregator->registerAdapter(new NewsApiAdapter());
            }
            
            if (config('services.guardian.key')) {
                $aggregator->registerAdapter(new GuardianApiAdapter());
            }
            
            if (config('services.nyt.key')) {
                $aggregator->registerAdapter(new NytApiAdapter());
            }
            
            return $aggregator;
        });
    }
    
    public function boot(): void
    {
        // Schedule article fetching
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            
            // Fetch articles every 30 minutes
            $schedule->command('news:fetch')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/news-fetch.log'));
        });
    }
}