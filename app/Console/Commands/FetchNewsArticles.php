<?php 
namespace App\Console\Commands;

use App\Contracts\NewsSourceInterface;
use App\Jobs\FetchNewsArticlesJob;
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
    
    public function handle(): int
    {
        $this->info('Starting news article fetch...');
        
        $source = $this->option('source');

        dispatch(new FetchNewsArticlesJob($source));

        $this->info('Job dispatched successfully. Check logs for details.');

        return Command::SUCCESS;
    }
}