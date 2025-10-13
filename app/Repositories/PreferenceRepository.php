<?php 
namespace App\Repositories;

use App\DTOs\UserPreferencesDTO;
use App\Models\UserPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PreferenceRepository
{
    private UserPreference $model;
    
    public function __construct(UserPreference $model)
    {
        $this->model = $model;
    }
    
    public function findByIdentifier(string $identifier): ?UserPreference
    {
        return $this->model->byIdentifier($identifier)->first();
    }
    
    public function findOrCreate(string $identifier): UserPreference
    {
        return $this->model->firstOrCreate(
            ['user_identifier' => $identifier],
            ['user_identifier' => $identifier]
        );
    }
    
    public function update(string $identifier, array $data): UserPreference
    {
        $preference = $this->findOrCreate($identifier);
        
        // Handle array fields properly
        foreach (['preferred_sources', 'preferred_categories', 'preferred_authors', 'excluded_keywords'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_values(array_unique($data[$field]));
            }
        }
        
        $preference->update($data);
        
        return $preference;
    }
    
    public function updateFromDTO(UserPreferencesDTO $dto): UserPreference
    {
        return $this->update($dto->userIdentifier, $dto->toArray());
    }
    
    public function reset(string $identifier): bool
    {
        $preference = $this->findByIdentifier($identifier);
        
        if ($preference) {
            $preference->resetPreferences();
            return true;
        }
        
        return false;
    }
    
    public function delete(string $identifier): bool
    {
        return $this->model->where('user_identifier', $identifier)->delete() > 0;
    }
    
    public function getUsersWithPreference(string $type, string $value): Collection
    {
        $field = match($type) {
            'source' => 'preferred_sources',
            'category' => 'preferred_categories',
            'author' => 'preferred_authors',
            default => null
        };
        
        if (!$field) {
            return collect();
        }
        
        return $this->model->whereJsonContains($field, $value)->get();
    }
    
    public function getDueForNotification(): Collection
    {
        return $this->model->dueForNotification()->get();
    }
}