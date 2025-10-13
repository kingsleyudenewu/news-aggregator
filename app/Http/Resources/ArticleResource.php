<?php 
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->when($request->input('include_content'), $this->content),
            'author' => $this->author,
            'source' => [
                'name' => $this->source_name,
                'id' => $this->source_id,
            ],
            'category' => $this->category,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at->toIso8601String(),
            'view_count' => $this->view_count,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}