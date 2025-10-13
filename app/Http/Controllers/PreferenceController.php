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
   public function __construct(private PreferenceRepository $repository) {}

    public function show(Request $request): JsonResponse
    {
        $preferences = $this->getPreferences($request);
        
        return response()->json([
            'data' => $preferences->toArray(),
            'meta' => [
                'has_preferences' => $preferences->hasPreferences(),
            ]
        ]);
    }

    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $preferences = UserPreferencesDTO::fromRequest($request->validated());
        $this->repository->updateFromDTO($preferences);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => $preferences->toArray(),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $identifier = $this->resolveIdentifier($request);
        
        if (!$this->repository->reset($identifier)) {
            return response()->json([
                'message' => 'No preferences found to reset'
            ], 404);
        }

        return response()->json([
            'message' => 'Preferences reset to defaults successfully'
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $identifier = $this->resolveIdentifier($request);
        
        if (!$this->repository->delete($identifier)) {
            return response()->json([
                'message' => 'No preferences found to delete'
            ], 404);
        }

        return response()->json([
            'message' => 'Preferences deleted successfully'
        ], 204);
    }

    private function getPreferences(Request $request): UserPreferencesDTO
    {
        $identifier = $this->resolveIdentifier($request);
        $model = $this->repository->findByIdentifier($identifier);

        return $model 
            ? UserPreferencesDTO::fromArray($model->toArray())
            : new UserPreferencesDTO(userIdentifier: $identifier);
    }

    private function resolveIdentifier(Request $request): string
    {
        return match (true) {
            $request->has('api_key_id') => 'api_' . $request->input('api_key_id'),
            $request->hasHeader('X-User-ID') => $request->header('X-User-ID'),
            default => session()->getId() ?: 'anonymous_' . uniqid(),
        };
    }
}