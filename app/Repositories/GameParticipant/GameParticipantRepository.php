<?php

namespace App\Repositories\GameParticipant;

use App\Models\GameParticipant;

class GameParticipantRepository implements GameParticipantRepositoryInterface
{
    /**
     * Lấy danh sách người tham gia game
     */
    public function getParticipantsByGameId($gameId)
    {
        return GameParticipant::where('game_id', $gameId)->get();
    }
} 