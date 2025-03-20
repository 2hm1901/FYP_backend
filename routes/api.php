<?php

use App\Http\Controllers\User\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\VenueController;
use App\Http\Controllers\User\GameController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Auth\ForgotPasswordController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Auth APIs
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

//User APIs
Route::get('/venue-owner', [UserController::class,'getOwnerInfo']);
Route::get('/getUser', [UserController::class, 'getUser']);

//Forgot Password APIs
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgot']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

//Venue APIs
Route::get("/getAllVenue", [VenueController::class,"getVenueList"]);
Route::get("/getVenueDetail/{id}", [VenueController::class,"getVenueDetail"]);
Route::get("/getBookingTable/{id}", [VenueController::class,"getBookingTable"]);
Route::get("/getMyVenues", [VenueController::class,"getMyVenues"]);
Route::post("/createNewVenue", [VenueController::class,"createNewVenue"]);
Route::put('/venues/{id}', [VenueController::class, 'updateVenue']);
Route::delete('/venues/{id}', [VenueController::class, 'deleteVenue']);

//Game APIs
Route::get("/getAllGame", [GameController::class,"getAllGame"]);
Route::get("/getGames", [GameController::class,"getGameList"]);
Route::get("/getGameDetail/{id}", [GameController::class,"getGameDetail"]);
Route::post("/createGame", [GameController::class,"createGame"]);
Route::get("/getGameStatus", [GameController::class,"getGameStatus"]);
Route::put("/updateGame", [GameController::class,"updateGame"]);
Route::delete("/cancelGame", [GameController::class,"cancelGame"]);

//Booking APIs
Route::get("/getBookings", [BookingController::class,"getBookings"]);
Route::get('/getBookedCourts/{id}', [BookingController::class, 'getBookedCourt']);
Route::post('/bookCourt', [BookingController::class, 'bookCourt']);
Route::get('/getBookedCourtList', [BookingController::class,'getBookedCourtList']);
Route::get('/getRequests', [BookingController::class,'getRequests']);
Route::put('/cancelCourt', [BookingController::class, 'cancelCourt']);
Route::post('/bookings/accept', [BookingController::class, 'acceptBooking'])->middleware('auth:sanctum');
Route::post('/bookings/decline', [BookingController::class, 'declineBooking'])->middleware('auth:sanctum');

//Notification APIs
Route::get('/notifications', [NotificationController::class, 'getNotifications']);
Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationAsRead'])->middleware('auth:sanctum');

//Profile APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::put('updateProfile', [ProfileController::class, 'update']);
});


