<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\VenueController;
use App\Http\Controllers\User\GameController;
use App\Http\Controllers\User\BookingController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Auth APIs
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

//Venue APIs
Route::get("/getAllVenue", [VenueController::class,"getVenueList"]);
Route::get("/getVenueDetail/{id}", [VenueController::class,"getVenueDetail"]);
Route::get("/getBookingTable/{id}", [VenueController::class,"getBookingTable"]);
Route::get("/getBookingTable/{id}", [VenueController::class,"getBookingTable"]);

//Game APIs
Route::get("/getAllGame", [GameController::class,"getGameList"]);
Route::get("/getGameDetail/{id}", [GameController::class,"getGameDetail"]);

//Booking APIs
Route::get('/getBookedCourts/{id}', [BookingController::class, 'getBookedCourt']);
Route::post('/bookCourt', [BookingController::class, 'bookCourt']);