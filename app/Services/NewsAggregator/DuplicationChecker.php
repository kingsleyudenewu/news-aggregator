<?php 
namespace App\Services\NewsAggregator;

use App\DTOs\ArticleDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicationChecker
{
    private array $checksumCache = [];
    
    public function isDuplicate(ArticleDTO $article): bool
    {
        $hash = $this->generateHash($article);
        
        // Check in-memory cache first
        if (isset($this->checksumCache[$hash])) {
            return true;
        }
        
        // Check database
        $exists = DB::table('articles')
            ->where('content_hash', $hash)
            ->exists();
        
        if (!$exists) {
            $this->checksumCache[$hash] = true;
        }
        
        return $exists;
    }
    
    public function removeDuplicates(Collection $articles): Collection
    {
        $seen = [];
        
        return $articles->filter(function ($article) use (&$seen) {
            $hash = $this->generateHash($article);
            
            if (isset($seen[$hash])) {
                return false;
            }
            
            $seen[$hash] = true;
            return !$this->isDuplicate($article);
        });
    }
    
    private function generateHash(ArticleDTO $article): string
    {
        // Use multiple fields for better duplicate detection
        return md5(
            strtolower($article->title) . 
            $article->url . 
            $article->sourceName
        );
    }
}