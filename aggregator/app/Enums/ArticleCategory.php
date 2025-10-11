<?php 
namespace App\Enums;

enum ArticleCategory: string
{
    case GENERAL = 'general';
    case BUSINESS = 'business';
    case ENTERTAINMENT = 'entertainment';
    case HEALTH = 'health';
    case SCIENCE = 'science';
    case SPORTS = 'sports';
    case TECHNOLOGY = 'technology';
    case POLITICS = 'politics';
    case WORLD = 'world';
    case OPINION = 'opinion';
    case LIFESTYLE = 'lifestyle';
    case EDUCATION = 'education';
    case ENVIRONMENT = 'environment';
    case FOOD = 'food';
    case TRAVEL = 'travel';
    case FASHION = 'fashion';
    case ART = 'art';
    case CULTURE = 'culture';
    
    public function displayName(): string
    {
        return match($this) {
            self::GENERAL => 'General',
            self::BUSINESS => 'Business',
            self::ENTERTAINMENT => 'Entertainment',
            self::HEALTH => 'Health',
            self::SCIENCE => 'Science',
            self::SPORTS => 'Sports',
            self::TECHNOLOGY => 'Technology',
            self::POLITICS => 'Politics',
            self::WORLD => 'World News',
            self::OPINION => 'Opinion',
            self::LIFESTYLE => 'Lifestyle',
            self::EDUCATION => 'Education',
            self::ENVIRONMENT => 'Environment',
            self::FOOD => 'Food & Dining',
            self::TRAVEL => 'Travel',
            self::FASHION => 'Fashion',
            self::ART => 'Art',
            self::CULTURE => 'Culture',
        };
    }
    
    public function getIcon(): string
    {
        return match($this) {
            self::GENERAL => '📰',
            self::BUSINESS => '💼',
            self::ENTERTAINMENT => '🎬',
            self::HEALTH => '🏥',
            self::SCIENCE => '🔬',
            self::SPORTS => '⚽',
            self::TECHNOLOGY => '💻',
            self::POLITICS => '🏛️',
            self::WORLD => '🌍',
            self::OPINION => '💭',
            self::LIFESTYLE => '🌟',
            self::EDUCATION => '🎓',
            self::ENVIRONMENT => '🌱',
            self::FOOD => '🍽️',
            self::TRAVEL => '✈️',
            self::FASHION => '👗',
            self::ART => '🎨',
            self::CULTURE => '🎭',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::GENERAL => '#6B7280',
            self::BUSINESS => '#3B82F6',
            self::ENTERTAINMENT => '#EC4899',
            self::HEALTH => '#10B981',
            self::SCIENCE => '#8B5CF6',
            self::SPORTS => '#F97316',
            self::TECHNOLOGY => '#0EA5E9',
            self::POLITICS => '#DC2626',
            self::WORLD => '#4F46E5',
            self::OPINION => '#7C3AED',
            self::LIFESTYLE => '#F59E0B',
            self::EDUCATION => '#14B8A6',
            self::ENVIRONMENT => '#22C55E',
            self::FOOD => '#EF4444',
            self::TRAVEL => '#06B6D4',
            self::FASHION => '#D946EF',
            self::ART => '#A855F7',
            self::CULTURE => '#6366F1',
        };
    }
    
    public static function fromString(string $category): ?self
    {
        $normalized = strtolower(trim($category));
        
        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }
        
        return null;
    }
    
    public static function toArray(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'name' => $case->displayName(),
                'icon' => $case->getIcon(),
                'color' => $case->getColor()
            ],
            self::cases()
        );
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}