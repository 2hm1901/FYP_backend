<?php

namespace App\Http\Resources\Game;

use Illuminate\Http\Resources\Json\JsonResource;

class GameDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'venue' => $this->venue ? [
                'id' => $this->venue->id,
                'name' => $this->venue->name,
                'location' => $this->venue->location,
            ] : null,
            'creator' => [
                'id' => $this->creator->id,
                'username' => $this->creator->username,
                'avatar' => $this->creator->avatar,
            ],
            'court_number' => $this->court_number,
            'game_date' => $this->game_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'current_players' => $this->current_players,
            'max_players' => $this->max_players,
            'skill_level_required' => $this->skill_level_required,
            'is_active' => $this->is_active,
            'participants' => $this->participants->map(function ($participant) {
                return [
                    'user_id' => $participant->user->id,
                    'username' => $participant->user->username,
                    'status' => $participant->status,
                    'avatar' => $participant->user->avatar,
                ];
            }),
        ];
    }
} 