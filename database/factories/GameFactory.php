<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Venue;
use App\Models\User;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition()
    {
        return [
            'venue_id' => Venue::factory(),
            'creator_id' => User::factory()->create(['user_type' => 'renter'])->id,
            'game_date' => $this->faker->date,
            'start_time' => $this->faker->time,
            'end_time' => $this->faker->time,
            'max_players' => $this->faker->numberBetween(2, 10),
            'current_players' => 1,
            'skill_level_required' => $this->faker->randomElement(['Newbie', 'TBY', 'TB', 'TB+', 'Pro']),
            'is_active' => $this->faker->boolean,
            'created_at' => now(),
        ];
    }
}

