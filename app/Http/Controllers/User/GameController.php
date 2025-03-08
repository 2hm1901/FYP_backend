<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;

class GameController extends Controller
{
    public function getAllGame(){
        $games = Game::where('is_active', 1)->orderBy('game_date', 'desc')->get();
        return response()->json($games);
    }
    //API to get all games
    public function getGameList(Request $request)
    {
        $creator_id = $request->user_id;
        $games = Game::where('creator_id', (int)$creator_id)
                 ->where('is_active', 1)
                 ->get();
        return response()->json($games);
    }
    //API to get the venue detail
    public function getGameDetail($id){
        $game = Game::find($id);
        return response()->json($game);
    }
    //API to create new game
    public function createGame(Request $request){
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
    public function getGameStatus(Request $request) {
        $id = $request->query('id');

        $gameExists = Game::where('id', $id)->exists();
    
        return response()->json([
            'is_recruited' => $gameExists
        ]);
    }

    //API to update game
    public function updateGame(Request $request) {
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
    public function cancelGame(Request $request) {
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


}

