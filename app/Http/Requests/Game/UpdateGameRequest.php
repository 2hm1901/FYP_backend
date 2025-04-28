<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|string|exists:games,id',
            'venue_id' => 'required|exists:venues,id',
            'creator_id' => 'required|exists:users,id',
            'court_number' => 'required|string',
            'game_date' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'current_players' => 'required|string|numeric|min:1',
            'max_players' => 'required|string|numeric|min:1',
            'skill_levels' => 'required|array',
        ];
    }
} 