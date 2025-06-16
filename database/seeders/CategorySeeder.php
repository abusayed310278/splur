<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Ride',
            'Gear',
            'Shop',
            'Art & Culture',
            'Quiet Calm',
            'Music',
            'Video',
        ];

        foreach ($categories as $name) {
            DB::table('categories')->insert([
                'category_name' => $name,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
