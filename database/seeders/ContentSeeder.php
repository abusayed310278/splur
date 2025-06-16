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
        // Fetch foreign keys
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $subcategoryIds = DB::table('sub_categories')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        // Image paths (relative to public/)
        $images = [
            'uploads/Blogs/1.png',
            'uploads/Blogs/2.jpg',
            'uploads/Blogs/3.jpg',
            'uploads/Blogs/4.jpg',
            'uploads/Blogs/5.jpg',
            'uploads/Blogs/6.jpg',
            'uploads/Blogs/7.jpg',
            'uploads/Blogs/8.jpg',
            'uploads/Blogs/9.jpg',
            'uploads/Blogs/10.jpg',
            'uploads/Blogs/11.jpg',
            'uploads/Blogs/12.jpg',
            'uploads/Blogs/13.jpg',
            'uploads/Blogs/14.jpg',
        ];

        // Insert 100 content records
        for ($i = 0; $i < 500; $i++) {
            $image1 = $images[array_rand($images)];
            $advertisingImage = $images[array_rand($images)];
            $imageLink = $images[array_rand($images)];
            $advertisingLink = $images[array_rand($images)];

            DB::table('contents')->insert([
                'category_id'       => fake()->randomElement($categoryIds),
                'subcategory_id'    => fake()->randomElement($subcategoryIds),
                'heading'           => fake()->sentence(6),
                'author'            => fake()->name,
                'date'              => fake()->date(),
                'sub_heading'       => fake()->sentence(10),
                'body1'             => fake()->paragraphs(5, true),
                'image1'            => $image1,
                'advertising_image' => $advertisingImage,
                'tags'              => json_encode(fake()->words(5)),
                'imageLink'         => asset($imageLink),
                'advertisingLink'   => asset($advertisingLink),
                'user_id'           => fake()->randomElement($userIds),
                'status'            => 'active',
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ]);
        }
    }
}
