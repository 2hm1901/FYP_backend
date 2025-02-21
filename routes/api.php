<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\VenueController;
use App\Http\Controllers\Auth\AuthController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get("/getAllVenue", [VenueController::class,"getVenueList"]);
Route::get("/getVenueDetail/{id}", [VenueController::class,"getVenueDetail"]);

Route::get("/getBookingTable/{id}", [VenueController::class,"getBookingTable"]);