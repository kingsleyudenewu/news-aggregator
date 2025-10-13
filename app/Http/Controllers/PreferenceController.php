<?php 
namespace App\Http\Controllers;

use App\DTOs\UserPreferencesDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Repositories\PreferenceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    private PreferenceRepository $repository;
    
    public function __construct(PreferenceRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get user preferences
     */
    public function show(Request $request): JsonResponse
    {
        $identifier = $this->getUserIdentifier($request);
        
        $preferences = $this->repository->findByIdentifier($identifier);
        
        if (!$preferences) {
            return response()->json([
                'data' => [
                    'user_identifier' => $identifier,
                    'preferred_sources' => [],
                    'preferred_categories' => [],
                    'preferred_authors' => [],
                    'excluded_keywords' => [],
                    'articles_per_page' => 20,
                    'default_sort' => 'published_at',
                    'language' => 'en',
                    'timezone' => 'UTC',
                ]
            ]);
        }
        
        return response()->json([
            'data' => [
                'user_identifier' => $preferences->user_identifier,
                'preferred_sources' => $preferences->preferred_sources ?? [],
                'preferred_categories' => $preferences->preferred_categories ?? [],
                'preferred_authors' => $preferences->preferred_authors ?? [],
                'excluded_keywords' => $preferences->excluded_keywords ?? [],
                'articles_per_page' => $preferences->articles_per_page,
                'default_sort' => $preferences->default_sort,
                'language' => $preferences->language,
                'timezone' => $preferences->timezone,
                'email_notifications' => $preferences->email_notifications,
                'notification_frequency' => $preferences->notification_frequency,
            ],
            'meta' => [
                'has_preferences' => $preferences->has_preferences,
                'summary' => $preferences->preferences_summary,
            ]
        ]);
    }
    
    /**
     * Update user preferences
     */
    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $identifier = $this->getUserIdentifier($request);
        
        $data = array_merge(
            ['user_identifier' => $identifier],
            $request->validated()
        );
        
        // Map request fields to model fields
        $mappedData = [
            'user_identifier' => $identifier,
            'preferred_sources' => $data['sources'] ?? null,
            'preferred_categories' => $data['categories'] ?? null,
            'preferred_authors' => $data['authors'] ?? null,
            'excluded_keywords' => $data['exclude'] ?? null,
            'articles_per_page' => $data['per_page'] ?? null,
            'default_sort' => $data['sort'] ?? null,
            'language' => $data['language'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'email_notifications' => $data['email_notifications'] ?? null,
            'notification_frequency' => $data['notification_frequency'] ?? null,
        ];
        
        // Remove null values
        $mappedData = array_filter($mappedData, fn($value) => $value !== null);
        
        $preferences = $this->repository->update($identifier, $mappedData);
        
        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => [
                'user_identifier' => $preferences->user_identifier,
                'preferred_sources' => $preferences->preferred_sources,
                'preferred_categories' => $preferences->preferred_categories,
                'preferred_authors' => $preferences->preferred_authors,
                'excluded_keywords' => $preferences->excluded_keywords,
                'articles_per_page' => $preferences->articles_per_page,
                'default_sort' => $preferences->default_sort,
                'language' => $preferences->language,
                'timezone' => $preferences->timezone,
            ]
        ]);
    }
    
    /**
     * Reset user preferences to defaults
     */
    public function reset(Request $request): JsonResponse
    {
        $identifier = $this->getUserIdentifier($request);
        
        $reset = $this->repository->reset($identifier);
        
        if (!$reset) {
            return response()->json([
                'message' => 'No preferences found to reset'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Preferences reset to defaults successfully'
        ]);
    }
    
    /**
     * Delete user preferences
     */
    public function destroy(Request $request): JsonResponse
    {
        $identifier = $this->getUserIdentifier($request);
        
        $deleted = $this->repository->delete($identifier);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'No preferences found to delete'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Preferences deleted successfully'
        ], 204);
    }
    
    /**
     * Get user identifier from request
     */
    private function getUserIdentifier(Request $request): string
    {
        if ($request->has('api_key_id')) {
            return 'api_' . $request->input('api_key_id');
        }
        
        if ($request->hasHeader('X-User-ID')) {
            return $request->header('X-User-ID');
        }
        
        return session()->getId() ?: 'anonymous_' . uniqid();
    }
}