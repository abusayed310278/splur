<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $subcategoryIds = DB::table('sub_categories')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        $images = [
            'images/1.png',
            'images/2.jpg',
            'images/3.jpg',
            'images/4.jpg',
            'images/5.jpg',
            'images/6.jpg',
            'images/7.jpg',
            'images/8.jpg',
            'images/9.jpg',
            'images/10.jpg',
            'images/11.jpg',
            'images/12.jpg',
            'images/13.jpg',
            'images/14.jpg',
        ];

        for ($i = 0; $i < 100; $i++) {
            DB::table('contents')->insert([
                'category_id' => fake()->randomElement($categoryIds),
                'subcategory_id' => fake()->randomElement($subcategoryIds),
                'heading' => fake()->sentence(6),
                'author' => fake()->name,
                'date' => fake()->date(),
                'sub_heading' => fake()->sentence(10),
                'body1' => fake()->paragraphs(5, true),
                'image1' => url($images[array_rand($images)]),
                'advertising_image' => url($images[array_rand($images)]),
                'tags' => json_encode(fake()->words(5)),
                'imageLink' => url($images[array_rand($images)]),
                'advertisingLink' => url($images[array_rand($images)]),
                'user_id' => fake()->randomElement($userIds),
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
