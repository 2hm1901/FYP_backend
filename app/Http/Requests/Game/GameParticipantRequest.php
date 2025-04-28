<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class GameParticipantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'game_id' => 'required|exists:games,id',
            'user_id' => 'required|exists:users,id',
            'creator_id' => 'sometimes|required|exists:users,id'
        ];
    }
} 