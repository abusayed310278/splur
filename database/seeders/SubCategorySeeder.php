<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Define subcategories for each category (7 categories, each with 7 subcategories)
        $subcategories = [
            1 => ['Mountain Ride', 'City Ride', 'Trail Ride', 'Off-road', 'Downhill', 'Enduro', 'Cross Country'],
            2 => ['Helmets', 'Gloves', 'Jackets', 'Boots', 'Goggles', 'Protective Gear', 'Rainwear'],
            3 => ['Apparel', 'Accessories', 'Bags', 'Footwear', 'Eyewear', 'Watches', 'Jewelry'],
            4 => ['Street Art', 'Galleries', 'Exhibitions', 'Murals', 'Sculptures', 'Workshops', 'Festivals'],
            5 => ['Mindfulness', 'Yoga', 'Meditation', 'Breathing Techniques', 'Relaxation', 'Self-care', 'Retreats'],
            6 => ['Indie Rock', 'Electronic', 'Jazz', 'Classical', 'Hip-Hop', 'Pop', 'Blues'],
            7 => ['Short Films', 'Documentaries', 'Animations', 'Experimental', 'Drama', 'Comedy', 'Action'],
        ];

        foreach ($subcategories as $categoryId => $subs) {
            foreach ($subs as $subName) {
                DB::table('sub_categories')->insert([
                    'category_id' => $categoryId,
                    'name' => $subName,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
