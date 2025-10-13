<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|min:2|max:255',
            'sources' => 'nullable|array',
            'sources.*' => 'string|in:NewsAPI,The Guardian,New York Times',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:50',
            'authors' => 'nullable|array',
            'authors.*' => 'string|max:100',
            'date_from' => 'nullable|date|before_or_equal:today',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|string|in:published_at,title,view_count',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
            'exclude' => 'nullable|array',
            'exclude.*' => 'string|max:50',
            'live_search' => 'nullable|boolean',
        ];
    }
    
    public function messages(): array
    {
        return [
            'q.min' => 'Search query must be at least 2 characters.',
            'sources.*.in' => 'Invalid news source selected.',
            'date_from.before_or_equal' => 'Date from cannot be in the future.',
            'date_to.after_or_equal' => 'Date to must be after date from.',
        ];
    }
}