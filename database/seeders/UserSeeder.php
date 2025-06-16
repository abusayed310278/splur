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
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'admin',
        ]);

        User::create([
            'email' => 'kongkonbdcalling45@gmail.com',
            'password' => Hash::make('Kongkon45'),
            'role' => 'admin',
        ]);

        User::create([
            'email' => 'editor@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'editor',
        ]);

        User::create([
            'email' => 'author@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'author',
        ]);

        User::create([
            'email' => 'subscriber@gmail.com',
            'password' => Hash::make('Password1945!'),
            'role' => 'user',
        ]);
    }
}
