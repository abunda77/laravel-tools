<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        $url = fake()->url();

        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'url' => $url,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(10),
            'image_url' => fake()->optional()->imageUrl(),
            'favicon_url' => fake()->optional()->url(),
            'domain' => parse_url($url, PHP_URL_HOST),
            'metadata' => null,
            'is_active' => true,
            'visited_count' => 0,
            'last_visited_at' => null,
        ];
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => BookmarkCategory::factory()->for(User::find($attributes['user_id']) ?? User::factory()),
        ]);
    }
}
