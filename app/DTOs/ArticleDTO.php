<?php 

namespace App\DTOs;

use Illuminate\Support\Carbon;

class ArticleDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $content,
        public readonly ?string $author,
        public readonly string $sourceName,
        public readonly string $sourceId,
        public readonly ?string $category,
        public readonly string $url,
        public readonly ?string $imageUrl,
        public readonly Carbon $publishedAt,
        public readonly ?string $externalId = null,
        public readonly array $metadata = [],
    ) {}

    public static function fromArray(array $data): ArticleDTO
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? null,
            content: $data['content'] ?? null,
            author: $data['author'] ?? null,
            sourceName: $data['source_name'] ?? '',
            sourceId: $data['source_id'] ?? '',
            category: $data['category'] ?? null,
            url: $data['url'] ?? '',
            imageUrl: $data['image_url'] ?? null,
            publishedAt: Carbon::parse($data['published_at']),
            externalId: $data['external_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'author' => $this->author,
            'source_name' => $this->sourceName,
            'source_id' => $this->sourceId,
            'category' => $this->category,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'published_at' => $this->publishedAt->toDateTimeString(),
            'external_id' => $this->externalId,
            'metadata' => $this->metadata,
            'content_hash' => $this->generateContentHash(),
        ];
    }

    private function generateContentHash(): string
    {
        return md5($this->title . $this->url . $this->sourceName);
    }
}