<?php 
namespace App\Http\Requests;

use App\Enums\ArticleCategory;
use App\Enums\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'source_name' => [
                'nullable',
                'string',
                Rule::in(array_column(NewsSource::cases(), 'value'))
            ],
            'source_id' => 'nullable|string|max:100',
            'category' => [
                'nullable',
                'string',
                Rule::in(ArticleCategory::values())
            ],
            'author' => 'nullable|string|max:100',
            'published_after' => 'nullable|date|before_or_equal:now',
            'published_before' => 'nullable|date|after_or_equal:published_after',
            'is_featured' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:5|max:100',
            'sort_by' => [
                'nullable',
                'string',
                Rule::in(['published_at', 'view_count', 'title', 'created_at'])
            ],
            'sort_order' => [
                'nullable',
                'string',
                Rule::in(['asc', 'desc'])
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'source_name.in' => 'The selected news source is invalid.',
            'category.in' => 'The selected category is invalid.',
            'published_after.before_or_equal' => 'The published after date cannot be in the future.',
            'published_before.after_or_equal' => 'The published before date must be after the published after date.',
            'per_page.min' => 'The minimum number of articles per page is 5.',
            'per_page.max' => 'The maximum number of articles per page is 100.',
        ];
    }
    
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        
        // Convert date strings to Carbon instances
        if (isset($validated['published_after'])) {
            $validated['published_after'] = \Carbon\Carbon::parse($validated['published_after']);
        }
        
        if (isset($validated['published_before'])) {
            $validated['published_before'] = \Carbon\Carbon::parse($validated['published_before']);
        }
        
        // Set defaults
        $validated['per_page'] = $validated['per_page'] ?? 20;
        $validated['sort_by'] = $validated['sort_by'] ?? 'published_at';
        $validated['sort_order'] = $validated['sort_order'] ?? 'desc';
        
        return $key ? ($validated[$key] ?? $default) : $validated;
    }
}