<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\GameParticipant;
use App\Models\Venue;
use Illuminate\Http\Request;
use App\Models\Game;
use Illuminate\Support\Facades\Validator;
use App\Events\GameJoinRequest;
use App\Events\GameRequestStatusUpdated;
use App\Events\PlayerKicked;
use App\Models\Notification;
use App\Models\User;

class GameController extends Controller
{
    public function getAllGame()
{
    $games = Game::where('is_active', 1)
        ->orderBy('game_date', 'desc')
        ->with('creator') // Load thông tin creator
        ->get();

    // Thêm thông tin venue vào mỗi game
    $gamesWithVenue = $games->map(function ($game) {
        $venue = Venue::find($game->venue_id);
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

    return response()->json($gamesWithVenue);
}
    //API to get all games
    public function getGameList(Request $request)
    {
        $creator_id = $request->user_id;
        $games = Game::where('creator_id', (int) $creator_id)
            ->where('is_active', 1)
            ->get();
        return response()->json($games);
    }

    public function getParticipatingGames($userId)
    {
        try {
            // Lấy danh sách game mà người dùng đang tham gia (status = accepted)
            $participatingGames = GameParticipant::where('user_id', $userId)
                ->where('status', 'accepted')
                ->with(['game.creator', 'game.venue'])
                ->get()
                ->map(function ($participant) {
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

            return response()->json([
                'success' => true,
                'data' => $participatingGames
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách game: ' . $e->getMessage()
            ], 500);
        }
    }

    //API to get the venue detail
    public function getGameDetail($id)
{
    $game = Game::with('creator')->find($id);

    if (!$game) {
        return response()->json(['message' => 'Game not found'], 404);
    }

    $venue = Venue::find($game->venue_id);

    // Lấy danh sách người tham gia từ GameParticipant
    $participants = GameParticipant::where('game_id', $game->id)
        ->with('user') // Load thông tin user
        ->get()
        ->map(function ($participant) {
            return [
                'user_id' => $participant->user->id,
                'username' => $participant->user->username,
                'status' => $participant->status,
                'avatar' => $participant->user->avatar,
                // 'karma' => $participant->user->karma ?? 0, // Giả sử User có karma
            ];
        });

    $response = [
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
            // 'karma' => $game->creator->karma ?? 0,
        ],
        'court_number' => $game->court_number,
        'game_date' => $game->game_date,
        'start_time' => $game->start_time,
        'end_time' => $game->end_time,
        'current_players' => $game->current_players,
        'max_players' => $game->max_players,
        'skill_level_required' => $game->skill_level_required,
        'is_active' => $game->is_active,
        'participants' => $participants, // Danh sách người tham gia
    ];

    return response()->json($response);
}
    //API to create new game
    public function createGame(Request $request)
{
    try {
        // Validate dữ liệu từ request
        $validatedData = $request->validate([
            'id' => 'required|string|unique:games,id', // id là bắt buộc, kiểu string, duy nhất
            'venue_id' => 'required|exists:venues,id',
            'creator_id' => 'required|exists:users,id',
            'court_number' => 'required|string',
            'game_date' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'current_players' => 'required|string|numeric|min:1',
            'max_players' => 'required|string|numeric|min:1',
            'skill_levels' => 'required|array',
        ]);

        // Chuyển skill_levels từ array thành string
        $skillLevelRequired = implode(', ', $validatedData['skill_levels']);

        // Tạo game mới trong database
        $game = Game::create([
            'id' => $validatedData['id'], // Sử dụng id từ request
            'venue_id' => $validatedData['venue_id'],
            'creator_id' => $validatedData['creator_id'],
            'court_number' => $validatedData['court_number'],
            'game_date' => $validatedData['game_date'],
            'start_time' => $validatedData['start_time'],
            'end_time' => $validatedData['end_time'],
            'current_players' => $validatedData['current_players'],
            'max_players' => $validatedData['max_players'],
            'skill_level_required' => $skillLevelRequired,
            'is_active' => true,
        ]);

        // Tạo bản ghi trong GameParticipant cho người tạo game
        GameParticipant::create([
            'game_id' => $game->id,
            'user_id' => $validatedData['creator_id'],
            'status' => 'accepted'
        ]);

        // Trả về response thành công
        return response()->json([
            'success' => true,
            'message' => 'Game đã được tạo thành công',
            'data' => $game
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
        ], 500);
    }
}

    //Api kiểm tra xem sân này đã được tuyển người rồi hay chưa
    public function getGameStatus(Request $request)
    {
        $id = $request->query('id');

        $gameExists = Game::where('id', $id)->exists();

        return response()->json([
            'is_recruited' => $gameExists
        ]);
    }

    //API to update game
    public function updateGame(Request $request)
    {
        try {
            // Validate dữ liệu từ request
            $validatedData = $request->validate([
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
            ]);

            // Chuyển skill_levels từ array thành string
            $skillLevelRequired = implode(', ', $validatedData['skill_levels']);

            // Tìm game trong database
            $game = Game::find($validatedData['id']);
            if (!$game) {
                return response()->json([
                    'success' => false,
                    'message' => 'Game không tồn tại'
                ], 404);
            }
            // Cập nhật game trong database
            $game->update([
                'venue_id' => $validatedData['venue_id'],
                'creator_id' => $validatedData['creator_id'],
                'court_number' => $validatedData['court_number'],
                'game_date' => $validatedData['game_date'],
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'current_players' => $validatedData['current_players'],
                'max_players' => $validatedData['max_players'],
                'skill_level_required' => $skillLevelRequired,
                'is_active' => true,
            ]);

            // Trả về response thành công
            return response()->json([
                'success' => true,
                'message' => 'Game đã được cập nhật thành công',
                'data' => $game
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    //API to cancel game
    public function cancelGame(Request $request)
{
    try {
        // Validate dữ liệu từ request
        $validatedData = $request->validate([
            'id' => 'required|string|exists:games,id',
        ]);

        // Tìm game trong database
        $game = Game::find($validatedData['id']);
        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game không tồn tại'
            ], 404);
        }

        // Xóa tất cả các game participant của game này
        GameParticipant::where('game_id', $validatedData['id'])->delete();

        // Xóa game khỏi database
        $game->delete();

        // Trả về response thành công
        return response()->json([
            'success' => true,
            'message' => 'Game đã được hủy thành công',
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
        ], 500);
    }
}

    // API để gửi yêu cầu tham gia game
    public function requestJoinGame(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'game_id' => 'required|exists:games,id',
                'user_id' => 'required|exists:users,id',
            ]);

            // Kiểm tra xem người dùng đã gửi yêu cầu chưa
            $existingRequest = GameParticipant::where('game_id', $validatedData['game_id'])
                ->where('user_id', $validatedData['user_id'])
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã gửi yêu cầu tham gia game này rồi'
                ], 400);
            }

            // Lấy thông tin game và người tạo
            $game = Game::with('creator')->find($validatedData['game_id']);
            $requester = User::find($validatedData['user_id']);

            // Tạo yêu cầu tham gia mới
            GameParticipant::create([
                'game_id' => $validatedData['game_id'],
                'user_id' => $validatedData['user_id'],
                'status' => 'pending'
            ]);

            // Tạo thông báo cho người tạo game
            $notification = Notification::create([
                'user_id' => $game->creator_id,
                'message' => "{$requester->username} muốn tham gia game của bạn",
                'is_read' => false
            ]);

            // Gửi event thông báo
            event(new GameJoinRequest(
                $game->creator_id,
                $requester->id,
                $requester->username,
                $game->id,
                $notification->id
            ));

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

    // API để chấp nhận yêu cầu tham gia
    public function acceptJoinRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'game_id' => 'required|exists:games,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $game = Game::with('creator')->findOrFail($request->game_id);
            $participant = GameParticipant::where('game_id', $request->game_id)
                ->where('user_id', $request->user_id)
                ->where('status', 'pending')
                ->firstOrFail();

            if ($game->current_players >= $game->max_players) {
                return response()->json([
                    'success' => false,
                    'message' => 'Game đã đủ số người chơi'
                ], 400);
            }

            $participant->status = 'accepted';
            $participant->save();

            $game->current_players += 1;
            $game->save();

            // Tạo thông báo cho người được chấp nhận
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'message' => "{$game->creator->username} đã chấp nhận yêu cầu tham gia game của bạn",
                'is_read' => false
            ]);

            // Gửi event thông báo
            event(new GameRequestStatusUpdated(
                $request->user_id,
                $game->id,
                $game->creator->username,
                'accepted',
                $notification->id
            ));

            return response()->json([
                'success' => true,
                'message' => 'Chấp nhận yêu cầu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi chấp nhận yêu cầu'
            ], 500);
        }
    }

    public function rejectJoinRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'game_id' => 'required|exists:games,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $game = Game::with('creator')->findOrFail($request->game_id);
            $participant = GameParticipant::where('game_id', $request->game_id)
                ->where('user_id', $request->user_id)
                ->where('status', 'pending')
                ->firstOrFail();

            $participant->status = 'rejected';
            $participant->save();

            // Tạo thông báo cho người bị từ chối
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'message' => "{$game->creator->username} đã từ chối yêu cầu tham gia game của bạn",
                'is_read' => false
            ]);

            // Gửi event thông báo
            event(new GameRequestStatusUpdated(
                $request->user_id,
                $request->game_id,
                $game->creator->username,
                'rejected',
                $notification->id
            ));

            return response()->json([
                'success' => true,
                'message' => 'Từ chối yêu cầu thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi từ chối yêu cầu'
            ], 500);
        }
    }

    // API để lấy danh sách yêu cầu tham gia
    public function getJoinRequests($gameId)
    {
        try {
            $requests = GameParticipant::where('game_id', $gameId)
                ->where('status', 'pending')
                ->with('user:id,username,avatar')
                ->get()
                ->map(function ($request) {
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

    // API để kick người chơi
    public function kickPlayer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'game_id' => 'required|exists:games,id',
                'user_id' => 'required|exists:users,id',
                'creator_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Kiểm tra xem người dùng có phải là host của game không
            $game = Game::with('creator')->findOrFail($request->game_id);
            if ($game->creator_id !== $request->creator_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền kick người chơi'
                ], 403);
            }

            // Tìm và xóa người chơi khỏi game
            $participant = GameParticipant::where('game_id', $request->game_id)
                ->where('user_id', $request->user_id)
                ->where('status', 'accepted')
                ->firstOrFail();

            $participant->delete();

            // Giảm số người chơi hiện tại
            $game->current_players -= 1;
            $game->save();

            // Tạo thông báo cho người bị kick
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'message' => "{$game->creator->username} đã kick bạn khỏi game",
                'is_read' => false
            ]);

            // Gửi event thông báo
            event(new PlayerKicked(
                $request->user_id,
                $game->id,
                $game->creator->username,
                $notification->id
            ));

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

