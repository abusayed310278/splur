<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content>
 */
class ContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => $this->faker->numberBetween(1, 7),
            'subcategory_id' => $this->faker->numberBetween(1, 7),
            'user_id' => $this->faker->numberBetween(1, 7),
            'heading' => $this->faker->sentence,
            'author' => $this->faker->name,
            'date' => $this->faker->date,
            'sub_heading' => $this->faker->sentence,
            'body1' => $this->faker->paragraph,
            'image1' => 'default.jpg',
            'advertising_image' => 'ad.jpg',
            'tags' => 'news,tech',
            'imageLink' => 'https://example.com/image',
            'advertisingLink' => 'https://example.com/ad',
            'status' => 'pending',
        ];
    }
}
