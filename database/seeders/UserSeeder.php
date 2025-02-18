<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'user_type' => 'admin',
            'skill_level' => 'TB',
        ]);

        User::factory()->count(4)->create(['user_type' => 'owner']);
        User::factory()->count(4)->create(['user_type' => 'renter']);
    }
}
