<?php

namespace Database\Factories;

use App\Models\GameParticipant;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameParticipantFactory extends Factory
{
    protected $model = GameParticipant::class;

    public function definition()
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
        ];
    }
}

