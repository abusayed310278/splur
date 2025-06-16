<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $subcategoryIds = DB::table('sub_categories')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        for ($i = 0; $i < 100; $i++) {
            DB::table('contents')->insert([
                'category_id' => fake()->randomElement($categoryIds),
                'subcategory_id' => fake()->randomElement($subcategoryIds),
                'heading' => fake()->sentence(6),
                'author' => fake()->name,
                'date' => fake()->date(),
                'sub_heading' => fake()->sentence(10),
                'body1' => fake()->paragraphs(5, true),
                'image1' => fake()->imageUrl(800, 600),
                'advertising_image' => fake()->imageUrl(800, 400),
                // 'tags' => implode(',', array_unique(fake()->words(5))),
                'imageLink' => fake()->url(),
                'advertisingLink' => fake()->url(),
                'user_id' => fake()->randomElement($userIds),
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
