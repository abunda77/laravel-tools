<?php

namespace Database\Factories;

use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookmarkCategoryFactory extends Factory
{
    protected $model = BookmarkCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
