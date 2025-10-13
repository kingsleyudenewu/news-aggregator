<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "title"=> $this->faker->sentence,
            "content"=> $this->faker->paragraph,
            'source_name' => $this->faker->randomElement(['NewsAPI', 'The Guardian', 'NYT']),
            'source_id' => $this->faker->uuid,
            'url' => $this->faker->unique()->url,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'author' => $this->faker->name,
            'description' => $this->faker->text(200),
            'category' => $this->faker->randomElement(['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology']),
            'image_url' => $this->faker->imageUrl(),
            'external_id' => $this->faker->uuid,
            'content_hash' => md5($this->faker->paragraph),
            'metadata' => null,
            'view_count' => $this->faker->numberBetween(0, 1000),
            'is_featured' => $this->faker->boolean(20),
        ];
    }
}
