<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;

class GameController extends Controller
{
    //API to get all games
    public function getGameList()
    {
        $games = Game::all();
        return response()->json($games);
    }
    //API to get the venue detail
    public function getGameDetail($id){
        $game = Game::find($id);
        return response()->json($game);
    }
}
