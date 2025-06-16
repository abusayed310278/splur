<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $subcategories = [
            ['category_id' => 1, 'name' => 'Mountain Ride'],
            ['category_id' => 1, 'name' => 'City Ride'],
            ['category_id' => 2, 'name' => 'Helmets'],
            ['category_id' => 2, 'name' => 'Gloves'],
            ['category_id' => 3, 'name' => 'Apparel'],
            ['category_id' => 3, 'name' => 'Accessories'],
            ['category_id' => 4, 'name' => 'Street Art'],
            ['category_id' => 4, 'name' => 'Galleries'],
            ['category_id' => 5, 'name' => 'Mindfulness'],
            ['category_id' => 5, 'name' => 'Yoga'],
            ['category_id' => 6, 'name' => 'Indie Rock'],
            ['category_id' => 6, 'name' => 'Electronic'],
            ['category_id' => 7, 'name' => 'Short Films'],
            ['category_id' => 7, 'name' => 'Documentaries'],
        ];

        foreach ($subcategories as $subcategory) {
            DB::table('sub_categories')->insert([
                'category_id' => $subcategory['category_id'],
                'name' => $subcategory['name'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
