<?php

namespace App\Repositories\GameParticipant;
 
interface GameParticipantRepositoryInterface
{
    public function getParticipantsByGameId($gameId);
} 