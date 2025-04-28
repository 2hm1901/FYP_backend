<?php

namespace App\Services\Game;

use App\Repositories\Game\GameRepository;
use App\Models\Notification;
use App\Events\GameJoinRequest;
use App\Events\GameRequestStatusUpdated;
use App\Events\PlayerKicked;
use Illuminate\Support\Facades\DB;

class GameService
{
    protected $gameRepository;

    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    public function getAllGames()
    {
        $games = $this->gameRepository->getAllActiveGames();

        return $games->map(function ($game) {
            $venue = $this->gameRepository->getVenueById($game->venue_id);
            return [
                'id' => $game->id,
                'venue' => $venue ? [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'location' => $venue->location,
                ] : null,
                'creator' => [
                    'id' => $game->creator->id,
                    'username' => $game->creator->username,
                    'avatar' => $game->creator->avatar,
                    'point' => $game->creator->point ?? 0,
                ],
                'court_number' => $game->court_number,
                'game_date' => $game->game_date,
                'start_time' => $game->start_time,
                'end_time' => $game->end_time,
                'current_players' => $game->current_players,
                'max_players' => $game->max_players,
                'skill_level_required' => $game->skill_level_required,
                'is_active' => $game->is_active,
            ];
        });
    }

    public function getGamesByCreator($creatorId)
    {
        return $this->gameRepository->getGamesByCreator($creatorId);
    }

    public function getGameDetail($id)
    {
        $game = $this->gameRepository->getGameWithParticipants($id);
        
        if (!$game) {
            return null;
        }

        $venue = $this->gameRepository->getVenueById($game->venue_id);

        return [
            'id' => $game->id,
            'venue' => $venue ? [
                'id' => $venue->id,
                'name' => $venue->name,
                'location' => $venue->location,
            ] : null,
            'creator' => [
                'id' => $game->creator->id,
                'username' => $game->creator->username,
                'avatar' => $game->creator->avatar,
            ],
            'court_number' => $game->court_number,
            'game_date' => $game->game_date,
            'start_time' => $game->start_time,
            'end_time' => $game->end_time,
            'current_players' => $game->current_players,
            'max_players' => $game->max_players,
            'skill_level_required' => $game->skill_level_required,
            'is_active' => $game->is_active,
            'participants' => $game->participants->map(function ($participant) {
                return [
                    'user_id' => $participant->user->id,
                    'username' => $participant->user->username,
                    'status' => $participant->status,
                    'avatar' => $participant->user->avatar,
                ];
            }),
        ];
    }

    public function createGame($data)
    {
        $skillLevelRequired = implode(', ', $data['skill_levels']);
        
        $gameData = [
            'id' => $data['id'],
            'venue_id' => $data['venue_id'],
            'creator_id' => $data['creator_id'],
            'court_number' => $data['court_number'],
            'game_date' => $data['game_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'current_players' => $data['current_players'],
            'max_players' => $data['max_players'],
            'skill_level_required' => $skillLevelRequired,
            'is_active' => true,
        ];

        return $this->gameRepository->createGame($gameData);
    }

    public function updateGame($id, $data)
    {
        $skillLevelRequired = implode(', ', $data['skill_levels']);
        
        $gameData = [
            'venue_id' => $data['venue_id'],
            'creator_id' => $data['creator_id'],
            'court_number' => $data['court_number'],
            'game_date' => $data['game_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'current_players' => $data['current_players'],
            'max_players' => $data['max_players'],
            'skill_level_required' => $skillLevelRequired,
            'is_active' => true,
        ];

        return $this->gameRepository->updateGame($id, $gameData);
    }

    public function cancelGame($id)
    {
        return $this->gameRepository->deleteGame($id);
    }

    public function getGameStatus($id)
    {
        return $this->gameRepository->getGameStatus($id);
    }

    public function getParticipatingGames($userId)
    {
        $participatingGames = $this->gameRepository->getParticipatingGames($userId);

        return $participatingGames->map(function ($participant) {
            return [
                'id' => $participant->game->id,
                'venue' => $participant->game->venue ? [
                    'id' => $participant->game->venue->id,
                    'name' => $participant->game->venue->name,
                    'location' => $participant->game->venue->location,
                ] : null,
                'creator' => [
                    'id' => $participant->game->creator->id,
                    'username' => $participant->game->creator->username,
                    'avatar' => $participant->game->creator->avatar,
                ],
                'court_number' => $participant->game->court_number,
                'game_date' => $participant->game->game_date,
                'start_time' => $participant->game->start_time,
                'end_time' => $participant->game->end_time,
                'current_players' => $participant->game->current_players,
                'max_players' => $participant->game->max_players,
                'skill_level_required' => $participant->game->skill_level_required,
                'is_active' => $participant->game->is_active,
                'status' => $participant->status
            ];
        });
    }

    public function requestJoinGame($gameId, $userId)
    {
        $game = $this->gameRepository->getGameById($gameId);
        $requester = $this->gameRepository->getUserById($userId);

        $this->gameRepository->createJoinRequest([
            'game_id' => $gameId,
            'user_id' => $userId,
            'status' => 'pending'
        ]);

        $notification = Notification::create([
            'user_id' => $game->creator_id,
            'message' => "{$requester->username} muốn tham gia game của bạn",
            'is_read' => false
        ]);

        event(new GameJoinRequest(
            $game->creator_id,
            $requester->id,
            $requester->username,
            $game->id,
            $notification->id
        ));

        return true;
    }

    public function acceptJoinRequest($gameId, $userId)
    {
        $game = $this->gameRepository->getGameById($gameId);
        $creator = $this->gameRepository->getUserById($game->creator_id);

        if ($game->current_players >= $game->max_players) {
            throw new \Exception('Game đã đủ số người chơi');
        }

        $this->gameRepository->updateParticipantStatus($gameId, $userId, 'accepted');
        $this->gameRepository->updateGamePlayersCount($gameId);

        $notification = Notification::create([
            'user_id' => $userId,
            'message' => "{$creator->username} đã chấp nhận yêu cầu tham gia game của bạn",
            'is_read' => false
        ]);

        event(new GameRequestStatusUpdated(
            $userId,
            $game->id,
            $creator->username,
            'accepted',
            $notification->id
        ));

        return true;
    }

    public function rejectJoinRequest($gameId, $userId)
    {
        $game = $this->gameRepository->getGameById($gameId);
        $creator = $this->gameRepository->getUserById($game->creator_id);

        $this->gameRepository->updateParticipantStatus($gameId, $userId, 'rejected');

        $notification = Notification::create([
            'user_id' => $userId,
            'message' => "{$creator->username} đã từ chối yêu cầu tham gia game của bạn",
            'is_read' => false
        ]);

        event(new GameRequestStatusUpdated(
            $userId,
            $game->id,
            $creator->username,
            'rejected',
            $notification->id
        ));

        return true;
    }

    public function getJoinRequests($gameId)
    {
        $requests = $this->gameRepository->getJoinRequests($gameId);

        return $requests->map(function ($request) {
            return [
                'id' => $request->id,
                'user' => [
                    'id' => $request->user->id,
                    'username' => $request->user->username,
                    'avatar' => $request->user->avatar,
                ],
                'created_at' => $request->created_at
            ];
        });
    }

    public function kickPlayer($gameId, $userId, $creatorId)
    {
        $game = $this->gameRepository->getGameById($gameId);
        
        if ($game->creator_id !== $creatorId) {
            throw new \Exception('Bạn không có quyền kick người chơi');
        }

        $creator = $this->gameRepository->getUserById($creatorId);

        $this->gameRepository->deleteParticipant($gameId, $userId);
        $this->gameRepository->updateGamePlayersCount($gameId, false);

        $notification = Notification::create([
            'user_id' => $userId,
            'message' => "{$creator->username} đã kick bạn khỏi game",
            'is_read' => false
        ]);

        event(new PlayerKicked(
            $userId,
            $game->id,
            $creator->username,
            $notification->id
        ));

        return true;
    }
} 