<?php 

namespace App\Exceptions;

use Exception;

class ArticleNotFoundException extends Exception
{
    protected int|string $articleId;
    protected ?array $searchCriteria;
    
    public function __construct(
        int|string $articleId = null,
        array $searchCriteria = null,
        int $code = 404,
        Exception $previous = null
    ) {
        $message = $articleId 
            ? "Article with ID {$articleId} not found"
            : "No articles found matching the criteria";
            
        parent::__construct($message, $code, $previous);
        
        $this->articleId = $articleId;
        $this->searchCriteria = $searchCriteria;
    }
    
    public function getArticleId(): int|string|null
    {
        return $this->articleId;
    }
    
    public function getSearchCriteria(): ?array
    {
        return $this->searchCriteria;
    }
    
    public static function withId(int|string $id): self
    {
        return new self($id);
    }
    
    public static function withCriteria(array $criteria): self
    {
        return new self(null, $criteria);
    }
    
    public function render($request)
    {
        if ($request->expectsJson()) {
            $response = [
                'error' => [
                    'message' => $this->getMessage(),
                    'code' => $this->getCode()
                ]
            ];
            
            if ($this->articleId) {
                $response['error']['article_id'] = $this->articleId;
            }
            
            if ($this->searchCriteria) {
                $response['error']['search_criteria'] = $this->searchCriteria;
            }
            
            return response()->json($response, $this->getCode());
        }
        
        return parent::render($request);
    }
    
    public function report(): bool
    {
        // Don't report 404 errors to avoid cluttering logs
        return false;
    }
}