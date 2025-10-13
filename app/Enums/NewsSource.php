<?php 
namespace App\Enums;

enum NewsSource: string
{
    case NEWS_API = 'newsapi';
    case GUARDIAN = 'guardian';
    case NYT = 'nyt';
    case BBC = 'bbc';
    case NEWS_CRED = 'newscred';
    case OPEN_NEWS = 'opennews';
    
    public function displayName(): string
    {
        return match($this) {
            self::NEWS_API => 'NewsAPI',
            self::GUARDIAN => 'The Guardian',
            self::NYT => 'New York Times',
            self::BBC => 'BBC News',
            self::NEWS_CRED => 'NewsCred',
            self::OPEN_NEWS => 'OpenNews',
        };
    }
    
    public function isActive(): bool
    {
        return match($this) {
            self::NEWS_API => !empty(config('services.newsapi.key')),
            self::GUARDIAN => !empty(config('services.guardian.key')),
            self::NYT => !empty(config('services.nyt.key')),
            self::BBC => !empty(config('services.bbc.key')),
            self::NEWS_CRED => !empty(config('services.newscred.key')),
            self::OPEN_NEWS => !empty(config('services.opennews.key')),
        };
    }
    
    public function getRateLimit(): int
    {
        return match($this) {
            self::NEWS_API => 500,  // per day
            self::GUARDIAN => 5000,
            self::NYT => 500,
            self::BBC => 1000,
            self::NEWS_CRED => 1000,
            self::OPEN_NEWS => 2000,
        };
    }
    
    public static function active(): array
    {
        return array_filter(
            self::cases(),
            fn($case) => $case->isActive()
        );
    }
    
    public static function toArray(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'name' => $case->displayName(),
                'active' => $case->isActive()
            ],
            self::cases()
        );
    }
}