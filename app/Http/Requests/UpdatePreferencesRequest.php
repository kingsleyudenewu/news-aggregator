<?php 

namespace App\Http\Requests;

use App\Enums\ArticleCategory;
use App\Enums\NewsSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'sources' => 'nullable|array',
            'sources.*' => [
                'string',
                Rule::in(array_map(fn($s) => $s->displayName(), NewsSource::cases()))
            ],
            'categories' => 'nullable|array',
            'categories.*' => [
                'string',
                Rule::in(ArticleCategory::values())
            ],
            'authors' => 'nullable|array',
            'authors.*' => 'string|max:100',
            'exclude' => 'nullable|array',
            'exclude.*' => 'string|max:50',
            'per_page' => 'nullable|integer|min:5|max:100',
            'sort' => [
                'nullable',
                'string',
                Rule::in(['published_at', 'view_count', 'title'])
            ],
            'language' => [
                'nullable',
                'string',
                Rule::in(['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ar'])
            ],
            'timezone' => 'nullable|timezone',
            'email_notifications' => 'nullable|boolean',
            'notification_frequency' => [
                'nullable',
                'string',
                Rule::in(['realtime', 'hourly', 'daily', 'weekly'])
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'sources.*.in' => 'One or more selected news sources are invalid.',
            'categories.*.in' => 'One or more selected categories are invalid.',
            'per_page.min' => 'Articles per page must be at least 5.',
            'per_page.max' => 'Articles per page cannot exceed 100.',
            'language.in' => 'The selected language is not supported.',
            'timezone.timezone' => 'The timezone must be a valid timezone identifier.',
        ];
    }
    
    public function prepareForValidation(): void
    {
        // Clean up empty arrays
        $data = $this->all();
        
        foreach (['sources', 'categories', 'authors', 'exclude'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field]);
            }
        }
        
        $this->merge($data);
    }
}