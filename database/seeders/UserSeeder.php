<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('h@i1912003'),
            'user_type' => 'admin',
            'phone_number' => '0123456789',
            'avatar' => null,
            'point' => 1000000,
            'skill_level' => 'TB',
            'email_verified_at' => now(),
        ]);
    }
}
