<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Editor',
            'email' => 'editor@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'editor',
        ]);

        User::create([
            'name' => 'Author',
            'email' => 'author@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'author',
        ]);

        User::create([
            'name' => 'Subscriber',
            'email' => 'subscriber@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'user',
        ]);
    }
}
