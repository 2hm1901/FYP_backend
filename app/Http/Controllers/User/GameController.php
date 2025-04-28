<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Game\GameService;
use App\Http\Requests\Game\CreateGameRequest;
use App\Http\Requests\Game\UpdateGameRequest;
use App\Http\Requests\Game\GameParticipantRequest;
use App\Http\Resources\Game\GameResource;
use App\Http\Resources\Game\GameDetailResource;
use Illuminate\Http\Request;
use App\Models\GameParticipant;
use App\Models\Venue;
use App\Models\Game;
use Illuminate\Support\Facades\Validator;
use App\Events\GameJoinRequest;
use App\Events\GameRequestStatusUpdated;
use App\Events\PlayerKicked;
use App\Models\Notification;
use App\Models\User;

class GameController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function getAllGame()
    {
        $games = $this->gameService->getAllGames();
        return response()->json($games);
    }

    public function getGameList(Request $request)
    {
        $games = $this->gameService->getGamesByCreator($request->user_id);
        return response()->json($games);
    }

    public function getParticipatingGames($userId)
    {
        try {
            $games = $this->gameService->getParticipatingGames($userId);
            return response()->json([
                'success' => true,
                'data' => $games
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách game: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getGameDetail($id)
    {
        $game = $this->gameService->getGameDetail($id);
        
        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }

        return response()->json($game);
    }

    public function createGame(CreateGameRequest $request)
    {
        try {
            $game = $this->gameService->createGame($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Game đã được tạo thành công',
                'data' => $game
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getGameStatus(Request $request)
    {
        $isRecruited = $this->gameService->getGameStatus($request->query('id'));
        return response()->json([
            'is_recruited' => $isRecruited
        ]);
    }

    public function updateGame(UpdateGameRequest $request)
    {
        try {
            $game = $this->gameService->updateGame($request->id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Game đã được cập nhật thành công',
                'data' => $game
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelGame(Request $request)
    {
        try {
            $this->gameService->cancelGame($request->id);
            return response()->json([
                'success' => true,
                'message' => 'Game đã được hủy thành công',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function requestJoinGame(GameParticipantRequest $request)
    {
        try {
            $this->gameService->requestJoinGame($request->game_id, $request->user_id);
            return response()->json([
                'success' => true,
                'message' => 'Yêu cầu tham gia game đã được gửi thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function acceptJoinRequest(GameParticipantRequest $request)
    {
        try {
            $this->gameService->acceptJoinRequest($request->game_id, $request->user_id);
            return response()->json([
                'success' => true,
                'message' => 'Chấp nhận yêu cầu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi chấp nhận yêu cầu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectJoinRequest(GameParticipantRequest $request)
    {
        try {
            $this->gameService->rejectJoinRequest($request->game_id, $request->user_id);
            return response()->json([
                'success' => true,
                'message' => 'Từ chối yêu cầu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi từ chối yêu cầu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getJoinRequests($gameId)
    {
        try {
            $requests = $this->gameService->getJoinRequests($gameId);
            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function kickPlayer(GameParticipantRequest $request)
    {
        try {
            $this->gameService->kickPlayer($request->game_id, $request->user_id, $request->creator_id);
            return response()->json([
                'success' => true,
                'message' => 'Đã kick người chơi thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kick người chơi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addPoints(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'points' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = User::findOrFail($request->user_id);
            $user->point += $request->points;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật điểm thành công',
                'data' => [
                    'point' => $user->point
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật điểm: ' . $e->getMessage()
            ], 500);
        }
    }
}

