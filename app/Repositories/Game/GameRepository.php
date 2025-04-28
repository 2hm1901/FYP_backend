<?php

namespace App\Repositories\Game;

use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\Venue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameRepository implements GameRepositoryInterface
{
    protected $game;
    protected $gameParticipant;
    protected $venue;
    protected $user;

    public function __construct(
        Game $game,
        GameParticipant $gameParticipant,
        Venue $venue,
        User $user
    ) {
        $this->game = $game;
        $this->gameParticipant = $gameParticipant;
        $this->venue = $venue;
        $this->user = $user;
    }

    public function getAllActiveGames()
    {
        return $this->game->where('is_active', 1)
            ->orderBy('game_date', 'desc')
            ->with('creator')
            ->get();
    }

    public function getGamesByCreator($creatorId)
    {
        return $this->game->where('creator_id', $creatorId)
            ->where('is_active', 1)
            ->get();
    }

    public function getGameById($id)
    {
        return $this->game->with('creator')->find($id);
    }

    public function getGameWithParticipants($id)
    {
        return $this->game->with(['creator', 'participants.user'])->find($id);
    }

    public function createGame($data)
    {
        return DB::transaction(function () use ($data) {
            $game = $this->game->create($data);
            
            $this->gameParticipant->create([
                'game_id' => $game->id,
                'user_id' => $data['creator_id'],
                'status' => 'accepted'
            ]);

            return $game;
        });
    }

    public function updateGame($id, $data)
    {
        $game = $this->game->findOrFail($id);
        $game->update($data);
        return $game;
    }

    public function deleteGame($id)
    {
        return DB::transaction(function () use ($id) {
            $this->gameParticipant->where('game_id', $id)->delete();
            return $this->game->findOrFail($id)->delete();
        });
    }

    public function getGameStatus($id)
    {
        return $this->game->where('id', $id)->exists();
    }

    public function getVenueById($id)
    {
        return $this->venue->find($id);
    }

    public function getUserById($id)
    {
        return $this->user->find($id);
    }

    public function getParticipatingGames($userId)
    {
        return $this->gameParticipant->where('user_id', $userId)
            ->where('status', 'accepted')
            ->with(['game.creator', 'game.venue'])
            ->get();
    }

    public function getJoinRequests($gameId)
    {
        return $this->gameParticipant->where('game_id', $gameId)
            ->where('status', 'pending')
            ->with('user:id,username,avatar')
            ->get();
    }

    public function createJoinRequest($data)
    {
        return $this->gameParticipant->create($data);
    }

    public function updateParticipantStatus($gameId, $userId, $status)
    {
        $participant = $this->gameParticipant->where('game_id', $gameId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participant->status = $status;
        $participant->save();

        return $participant;
    }

    public function deleteParticipant($gameId, $userId)
    {
        return $this->gameParticipant->where('game_id', $gameId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function updateGamePlayersCount($gameId, $increment = true)
    {
        $game = $this->game->findOrFail($gameId);
        $game->current_players += $increment ? 1 : -1;
        $game->save();
        return $game;
    }

    /**
     * Tìm game theo id
     */
    public function findById($id)
    {
        return Game::find($id);
    }

    /**
     * Xoá game
     */
    public function delete($game)
    {
        return $game->delete();
    }
} 